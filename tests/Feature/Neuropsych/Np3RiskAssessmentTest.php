<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\RiskAssessment;
use App\Models\Role;
use App\Models\ScaleResult;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\NeuroPsych\RiskAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP3 — suicide-risk assessment: stratification, the mandatory safety plan,
 * heightened-RBAC (view_sensitive) gate, and the active-risk banner signal.
 * Patient-safety critical — exercised thoroughly.
 */
class Np3RiskAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Role $role;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $this->role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]);
        $this->doctorUser = User::create(['name' => 'Doc', 'email' => 'np3-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $this->role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'psychiatry']);

        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();

        $this->patient = Patient::create(['full_name' => 'Pat', 'phone' => '0500005555']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-NP3-1'])->save();
    }

    private function withSensitive(): void
    {
        $this->role->update(['permissions' => ['psychiatry.view', 'psychiatry.create', 'psychiatry.update', 'psychiatry.view_sensitive']]);
    }

    private function withoutSensitive(): void
    {
        $this->role->update(['permissions' => ['psychiatry.view', 'psychiatry.create', 'psychiatry.update']]);
    }

    // ── stratification (service) ──
    public function test_cssrs_stratification_bands(): void
    {
        $svc = app(RiskAssessmentService::class);
        $this->assertSame('high', $svc->stratifyCssrs(['q6' => true]));
        $this->assertSame('high', $svc->stratifyCssrs(['q5' => true]));
        $this->assertSame('moderate', $svc->stratifyCssrs(['q4' => true]));
        $this->assertSame('moderate', $svc->stratifyCssrs(['q3' => true]));
        $this->assertSame('low', $svc->stratifyCssrs(['q1' => true]));
        $this->assertSame('none', $svc->stratifyCssrs(['q1' => false]));
    }

    // ── HTTP: write + safety plan + RBAC ──
    public function test_high_risk_requires_safety_plan(): void
    {
        $this->withSensitive();

        $this->actingAs($this->doctorUser)
            ->from('/doctor/psychiatry/encounters')
            ->post('/doctor/psychiatry/risk', [
                'patient_id' => $this->patient->id,
                'answers' => ['q5' => true],   // → high
                'safety_plan' => '',           // missing!
            ])->assertSessionHasErrors('safety_plan');

        $this->assertDatabaseMissing('risk_assessments', ['patient_id' => $this->patient->id]);
    }

    public function test_high_risk_with_safety_plan_is_recorded_active(): void
    {
        $this->withSensitive();

        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/risk', [
            'patient_id' => $this->patient->id,
            'answers' => ['q5' => true],
            'safety_plan' => 'Remove means; 24/7 crisis line; follow-up in 48h.',
        ])->assertRedirect();

        $r = RiskAssessment::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame('high', $r->risk_level);
        $this->assertTrue($r->is_active);
    }

    public function test_low_risk_does_not_require_safety_plan(): void
    {
        $this->withSensitive();
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/risk', [
            'patient_id' => $this->patient->id,
            'answers' => ['q1' => true], // low
        ])->assertRedirect();

        $this->assertDatabaseHas('risk_assessments', ['patient_id' => $this->patient->id, 'risk_level' => 'low']);
    }

    public function test_sensitive_route_blocked_without_view_sensitive_permission(): void
    {
        $this->withoutSensitive();

        $resp = $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/risk', [
            'patient_id' => $this->patient->id,
            'answers' => ['q5' => true], 'safety_plan' => 'plan',
        ]);
        $this->assertContains($resp->status(), [403, 302]);
        $this->assertDatabaseMissing('risk_assessments', ['patient_id' => $this->patient->id]);
    }

    // ── active-risk banner signal ──
    public function test_active_risk_reflects_high_assessment(): void
    {
        $svc = app(RiskAssessmentService::class);
        $this->assertNull($svc->activeRiskFor($this->patient->id));

        $svc->recordCssrs($this->patient->id, null, ['q6' => true], 'safety plan documented');
        $active = $svc->activeRiskFor($this->patient->id);
        $this->assertSame('high', $active['level']);
    }

    public function test_active_risk_falls_back_to_flagged_questionnaire(): void
    {
        $svc = app(RiskAssessmentService::class);
        // A flagged PHQ-9 (item 9 > 0) with no assessment → moderate banner.
        ScaleResult::build($this->patient->id, 'phq9', array_fill(0, 9, 1), 'patient')->save();

        $active = $svc->activeRiskFor($this->patient->id);
        $this->assertSame('moderate', $active['level']);
        $this->assertStringContainsString('PHQ9', $active['reason_en']);
    }
}

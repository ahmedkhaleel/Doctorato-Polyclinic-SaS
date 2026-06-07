<?php

namespace Tests\Feature\Physio;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\ScaleResult;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\NeuroPsych\ScaleEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PT-7 — physiotherapy patient-reported outcome measures (ODI/NDI/LEFS) ride on
 * the shared ScaleEngine/ScaleResult: scoring + severity bands are correct, MCID
 * metadata is exposed, and a doctor can capture a PROM that's scored server-side.
 */
class PhysiotherapyPromTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'PT Doc', 'email' => 'prom-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'physiotherapy']);

        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();

        $this->patient = Patient::create(['full_name' => 'PROM P', 'phone' => '0500007777']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PROM-1'])->save();
    }

    public function test_physiotherapy_proms_are_registered_with_mcid(): void
    {
        $proms = ScaleEngine::forModule('physiotherapy');
        $this->assertArrayHasKey('odi', $proms);
        $this->assertArrayHasKey('ndi', $proms);
        $this->assertArrayHasKey('lefs', $proms);

        $this->assertSame(10, ScaleEngine::mcid('odi'));
        $this->assertFalse(ScaleEngine::higherIsBetter('odi'));
        $this->assertTrue(ScaleEngine::higherIsBetter('lefs'));
    }

    public function test_odi_scores_and_severity_bands(): void
    {
        // All sections = 3 → raw 30 → "Severe disability" (21–30).
        $answers = array_fill(0, 10, 3);
        $this->assertSame(30, ScaleEngine::score('odi', $answers));
        $this->assertSame('Severe disability', ScaleEngine::severity('odi', 30)['en']);

        // Minimal.
        $this->assertSame('Minimal disability', ScaleEngine::severity('odi', 5)['en']);
    }

    public function test_lefs_higher_is_better_band(): void
    {
        // 20 items × 4 = 80 → full function.
        $this->assertSame(80, ScaleEngine::score('lefs', array_fill(0, 20, 4)));
        $this->assertSame('Minimal / full function', ScaleEngine::severity('lefs', 80)['en']);
    }

    public function test_doctor_can_capture_a_prom_and_it_is_scored(): void
    {
        $answers = array_fill(0, 10, 2); // ODI raw 20

        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/scales", [
                'scale_key' => 'odi', 'answers' => $answers,
            ])->assertRedirect();

        $r = ScaleResult::where('patient_id', $this->patient->id)->where('scale_key', 'odi')->firstOrFail();
        $this->assertSame(20, (int) $r->score);
        $this->assertSame('Moderate disability', $r->severity);
    }

    public function test_a_non_physiotherapy_scale_is_rejected(): void
    {
        $this->actingAs($this->doctorUser)
            ->from("/doctor/physiotherapy/patients/{$this->patient->id}")
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/scales", [
                'scale_key' => 'phq9', 'answers' => [0, 0, 0],
            ])->assertSessionHasErrors('scale_key');

        $this->assertSame(0, ScaleResult::where('patient_id', $this->patient->id)->count());
    }
}

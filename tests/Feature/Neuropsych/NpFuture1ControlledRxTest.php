<?php

namespace Tests\Feature\Neuropsych;

use App\Models\ControlledPrescription;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ControlledRx\ControlledRxManager;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP-Future-1 (layer B) — controlled-substance e-prescribing workflow:
 * draft → sign (MFA required) → submit (internal gateway authorizes) →
 * dispensed; plus the view_sensitive RBAC gate and gateway resolution.
 */
class NpFuture1ControlledRxTest extends TestCase
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
        $this->doctorUser = User::create(['name' => 'Doc', 'email' => 'npf1-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $this->role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'psychiatry']);

        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();

        $this->patient = Patient::create(['full_name' => 'Pat', 'phone' => '0500009999']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-NPF1-1'])->save();
    }

    private function sensitive(): void
    {
        $this->role->update(['permissions' => ['psychiatry.view', 'psychiatry.create', 'psychiatry.update', 'psychiatry.view_sensitive']]);
    }

    private function draft(): ControlledPrescription
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/controlled-rx', [
            'patient_id' => $this->patient->id, 'drug' => 'Methylphenidate', 'schedule' => 'II', 'dose' => '10mg', 'quantity' => '30 tabs',
        ])->assertRedirect();

        return ControlledPrescription::where('patient_id', $this->patient->id)->latest('id')->firstOrFail();
    }

    public function test_default_gateway_is_internal(): void
    {
        $this->assertSame('internal', app(ControlledRxManager::class)->active()->code());
    }

    public function test_rbac_gate_blocks_without_view_sensitive(): void
    {
        $this->role->update(['permissions' => ['psychiatry.view', 'psychiatry.create', 'psychiatry.update']]);
        $resp = $this->actingAs($this->doctorUser)->get('/doctor/psychiatry/controlled-rx');
        $this->assertContains($resp->status(), [403, 302]);
    }

    public function test_full_workflow_draft_sign_submit_dispense(): void
    {
        $this->sensitive();
        $rx = $this->draft();
        $this->assertSame('draft', $rx->status);

        // Sign without MFA → rejected.
        $this->actingAs($this->doctorUser)->from('/doctor/psychiatry/controlled-rx')
            ->post("/doctor/psychiatry/controlled-rx/{$rx->id}/sign", ['mfa_token' => ''])
            ->assertSessionHasErrors('mfa_token');
        $this->assertSame('draft', $rx->fresh()->status);

        // Sign with MFA → signed + hash.
        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/controlled-rx/{$rx->id}/sign", ['mfa_token' => '123456'])->assertRedirect();
        $rx->refresh();
        $this->assertSame('signed', $rx->status);
        $this->assertNotEmpty($rx->signature_hash);

        // Submit → internal gateway authorizes with a serial.
        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/controlled-rx/{$rx->id}/submit")->assertRedirect();
        $rx->refresh();
        $this->assertSame('authorized', $rx->status);
        $this->assertStringStartsWith('CRX-', (string) $rx->external_ref);

        // Dispense.
        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/controlled-rx/{$rx->id}/dispense")->assertRedirect();
        $this->assertSame('dispensed', $rx->fresh()->status);
    }

    public function test_cannot_submit_unsigned(): void
    {
        $this->sensitive();
        $rx = $this->draft();
        $this->actingAs($this->doctorUser)->from('/doctor/psychiatry/controlled-rx')
            ->post("/doctor/psychiatry/controlled-rx/{$rx->id}/submit")
            ->assertSessionHasErrors('signature');
        $this->assertSame('draft', $rx->fresh()->status);
    }
}

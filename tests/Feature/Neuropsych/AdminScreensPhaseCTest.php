<?php

namespace Tests\Feature\Neuropsych;

use App\Models\ControlledPrescription;
use App\Models\Doctor;
use App\Models\MedicationMonitoring;
use App\Models\MedicationPlan;
use App\Models\Patient;
use App\Models\RiskAssessment;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase C — sensitive safety/compliance screens: risk register + controlled
 * substances (view_sensitive-gated) and the medications/monitoring queue.
 */
class AdminScreensPhaseCTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'phaseC-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        ModuleManager::enable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::flushStaticCache();

        $this->doctor = Doctor::create(['name_ar' => 'د. تجربة', 'name_en' => 'Dr Test', 'status' => 'active', 'module' => 'psychiatry']);
        $this->patient = Patient::create(['full_name' => 'Safety Patient', 'phone' => '0500003333']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PC-1'])->save();
    }

    public function test_risk_register_lists_active_risks(): void
    {
        RiskAssessment::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'type' => 'suicide', 'tool' => 'c-ssrs',
            'answers' => [], 'risk_level' => 'high', 'safety_plan' => 'Plan here', 'is_active' => true, 'assessed_at' => now(),
        ]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/risk')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/RiskRegister')
                ->has('rows.data', 1)
                ->where('rows.data.0.risk_level', 'high')
                ->where('rows.data.0.has_safety_plan', true)
            );
    }

    public function test_medications_shows_overdue_monitoring(): void
    {
        $plan = MedicationPlan::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'module' => 'psychiatry',
            'drug' => 'Clozapine', 'started_at' => now()->subMonths(2),
        ]);
        MedicationMonitoring::create([
            'medication_plan_id' => $plan->id, 'patient_id' => $this->patient->id,
            'type' => 'ANC', 'due_at' => now()->subDays(3), 'status' => 'due',
        ]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/medications')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Medications')
                ->has('overdue', 1)
                ->has('plans.data', 1)
                ->where('overdue.0.drug', 'Clozapine')
            );
    }

    public function test_controlled_audit_lists_prescriptions(): void
    {
        ControlledPrescription::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'module' => 'psychiatry',
            'drug' => 'Methylphenidate', 'schedule' => 'II', 'quantity' => '30 tabs', 'status' => 'draft', 'gateway' => 'internal',
        ]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/controlled')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Controlled')
                ->has('rx.data', 1)
                ->where('rx.data.0.drug', 'Methylphenidate')
            );
    }

    public function test_sensitive_screens_require_view_sensitive(): void
    {
        // Admin with module view but NOT view_sensitive.
        $this->admin->role->update(['permissions' => ['psychiatry.view']]);

        // Non-sensitive screen ok.
        $this->actingAs($this->admin)->get('/admin/psychiatry/medications')->assertOk();

        // Sensitive screens blocked.
        $this->assertContains($this->actingAs($this->admin)->get('/admin/psychiatry/risk')->status(), [403, 302]);
        $this->assertContains($this->actingAs($this->admin)->get('/admin/psychiatry/controlled')->status(), [403, 302]);
    }

    public function test_viewing_risk_register_writes_a_sensitive_access_log(): void
    {
        RiskAssessment::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'type' => 'suicide', 'tool' => 'c-ssrs',
            'answers' => [], 'risk_level' => 'high', 'safety_plan' => 'Plan', 'is_active' => true, 'assessed_at' => now(),
        ]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/risk')->assertOk();

        // The sensitive read is provable in the medical access audit trail.
        $this->assertDatabaseHas('medical_data_access_logs', [
            'user_id' => $this->admin->id,
            'patient_id' => $this->patient->id,
            'access_type' => 'view_medical',
            'data_category' => 'sensitive_medical',
        ]);
    }

    public function test_viewing_controlled_log_writes_a_sensitive_access_log(): void
    {
        ControlledPrescription::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'module' => 'psychiatry',
            'drug' => 'Methylphenidate', 'schedule' => 'II', 'quantity' => '30 tabs', 'status' => 'draft', 'gateway' => 'internal',
        ]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/controlled')->assertOk();

        $this->assertDatabaseHas('medical_data_access_logs', [
            'user_id' => $this->admin->id,
            'patient_id' => $this->patient->id,
            'data_category' => 'sensitive_medical',
        ]);
    }
}

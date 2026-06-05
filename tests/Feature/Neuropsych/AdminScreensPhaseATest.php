<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\NeuropsychEncounter;
use App\Models\ObgynProfile;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase A — super-admin oversight screens: Cases + Encounters for psychiatry,
 * neurology, and OB/GYN. Read-only render + data presence + RBAC.
 */
class AdminScreensPhaseATest extends TestCase
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
        $this->admin = User::create(['name' => 'Admin', 'email' => 'phaseA-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        ModuleManager::enable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::enable('obgyn');
        ModuleManager::flushStaticCache();

        $this->doctor = Doctor::create(['name_ar' => 'د. تجربة', 'name_en' => 'Dr Test', 'status' => 'active', 'module' => 'psychiatry']);
        $this->patient = Patient::create(['full_name' => 'Case Patient', 'phone' => '0500001111']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PA-1'])->save();
    }

    public function test_neuropsych_cases_lists_patients_with_encounters(): void
    {
        NeuropsychEncounter::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'module' => 'psychiatry',
            'encounter_date' => now()->toDateString(), 'note_format' => 'soap',
        ]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/cases')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Cases')
                ->where('module', 'psychiatry')
                ->has('cases.data', 1)
                ->where('cases.data.0.full_name', 'Case Patient')
                ->where('cases.data.0.encounters_count', 1)
            );
    }

    public function test_neuropsych_encounters_log_renders_with_billing_status(): void
    {
        NeuropsychEncounter::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'module' => 'neurology',
            'encounter_date' => now()->toDateString(), 'note_format' => 'soap', 'cost' => 250,
        ]);

        $this->actingAs($this->admin)->get('/admin/neurology/encounters')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Encounters')
                ->where('module', 'neurology')
                ->has('encounters.data', 1)
                ->where('encounters.data.0.billed', false)
                ->where('encounters.data.0.completed', false)
            );
    }

    public function test_obgyn_cases_lists_profiles(): void
    {
        ObgynProfile::create(['patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'gravida' => 2, 'para' => 1]);

        $this->actingAs($this->admin)->get('/admin/obgyn/cases')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Obgyn/Cases')
                ->has('cases.data', 1)
                ->where('cases.data.0.gravida', 2)
            );
    }

    public function test_cases_screen_requires_module_permission(): void
    {
        // Admin panel is role-name gated (admin/super_admin); per-module access
        // is then enforced by the permission middleware. Narrow this admin's
        // permissions to psychiatry only and confirm obgyn is blocked.
        $this->admin->role->update(['permissions' => ['psychiatry.view']]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/cases')->assertOk();

        $resp = $this->actingAs($this->admin)->get('/admin/obgyn/cases');
        $this->assertContains($resp->status(), [403, 302]);
    }
}

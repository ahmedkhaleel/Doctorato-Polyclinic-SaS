<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F3 — patient neuropsych pages (scales/diaries) are route-gated by
 * module:psychiatry,neurology: blocked when BOTH are disabled, reachable
 * when either is enabled.
 */
class PatientNeuropsychRouteGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $patRole = Role::firstOrCreate(['name' => 'patient'], ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $this->patientUser = User::create(['name' => 'Pat', 'email' => 'f3-pat@test.com', 'password' => bcrypt('x'), 'role_id' => $patRole->id, 'is_active' => true]);
        $patient = Patient::create(['full_name' => 'Pat', 'phone' => '0500007777']);
        $patient->forceFill(['user_id' => $this->patientUser->id, 'is_active' => true, 'file_number' => 'PAT-F3-1'])->save();
    }

    public function test_blocked_when_both_modules_disabled(): void
    {
        ModuleManager::disable('psychiatry');
        ModuleManager::disable('neurology');
        ModuleManager::flushStaticCache();

        // Redirected away (not a 200) by the module gate.
        $this->actingAs($this->patientUser)->get('/ar/patient/neuropsych/scales')
            ->assertRedirect();
        $this->actingAs($this->patientUser)->get('/ar/patient/neuropsych/diaries')
            ->assertRedirect();
    }

    public function test_reachable_when_neurology_enabled(): void
    {
        ModuleManager::disable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::flushStaticCache();

        $this->actingAs($this->patientUser)->get('/ar/patient/neuropsych/scales')->assertOk();
        $this->actingAs($this->patientUser)->get('/ar/patient/neuropsych/diaries')->assertOk();
    }

    public function test_overview_landing_renders_with_own_data(): void
    {
        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();

        $this->actingAs($this->patientUser)->get('/ar/patient/neuropsych')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Patient/Neuropsych/Overview')
                ->has('stats')
                ->has('upcoming')
            );
    }
}

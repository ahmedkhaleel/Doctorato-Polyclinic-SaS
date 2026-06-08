<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\MedicationPlan;
use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Models\RiskAssessment;
use App\Models\Role;
use App\Models\ScaleResult;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Shared psychiatry/neurology doctor cockpit: renders with caseload KPIs +
 * flagged scales, and the risk register is gated by {module}.view_sensitive.
 */
class NeuropsychDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function setupDoctor(string $module, array $perms): User
    {
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => $perms, 'is_system' => true]);
        $role->update(['permissions' => $perms]);
        $user = User::create(['name' => 'D', 'email' => "np-dash-{$module}@test.com", 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $user->id, 'status' => 'active', 'module' => $module]);
        ModuleManager::flushStaticCache();
        ModuleManager::enable($module);
        ModuleManager::flushStaticCache();

        $p = Patient::create(['full_name' => 'NP P', 'phone' => '0500'.random_int(100000, 999999)]);
        $p->forceFill(['is_active' => true, 'file_number' => 'PAT-NPD-'.$module])->save();
        $enc = NeuropsychEncounter::create(['patient_id' => $p->id, 'doctor_id' => $doctor->id, 'module' => $module, 'encounter_date' => now()->toDateString(), 'note_format' => 'soap', 'subjective' => 's', 'assessment' => 'a', 'plan' => 'p', 'cost' => 350]);
        ScaleResult::create(['patient_id' => $p->id, 'scale_key' => $module === 'psychiatry' ? 'phq9' : 'hit6', 'answers' => [], 'score' => 20, 'severity' => 'severe', 'flag' => true, 'entered_by' => 'doctor', 'neuropsych_encounter_id' => $enc->id, 'taken_at' => now()]);
        MedicationPlan::create(['patient_id' => $p->id, 'doctor_id' => $doctor->id, 'module' => $module, 'drug' => 'X', 'dose' => '10mg', 'frequency' => 'OD', 'started_at' => now()->toDateString(), 'is_controlled' => false]);
        RiskAssessment::create(['patient_id' => $p->id, 'doctor_id' => $doctor->id, 'type' => 'suicide', 'tool' => 'c-ssrs', 'answers' => [], 'risk_level' => 'high', 'is_active' => true, 'assessed_at' => now()]);

        return $user;
    }

    public function test_psychiatry_dashboard_renders_with_sensitive_access(): void
    {
        $user = $this->setupDoctor('psychiatry', ['*']);

        $this->actingAs($user)->get('/doctor/psychiatry')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Doctor/Neuropsych/Dashboard')
                ->where('module', 'psychiatry')
                ->where('canSeeSensitive', true)
                ->where('stats.active_cases', 1)
                ->where('stats.encounters_this_month', 1)
                ->where('stats.active_medications', 1)
                ->has('flaggedScales', 1)
                ->has('riskRegister', 1)
            );
    }

    public function test_neurology_dashboard_hides_risk_without_sensitive(): void
    {
        $user = $this->setupDoctor('neurology', ['neurology.view', 'neurology.create', 'neurology.update']);

        $this->actingAs($user)->get('/doctor/neurology')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Doctor/Neuropsych/Dashboard')
                ->where('module', 'neurology')
                ->where('canSeeSensitive', false)
                ->where('riskRegister', [])
            );
    }
}

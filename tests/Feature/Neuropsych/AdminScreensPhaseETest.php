<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase E — dashboard KPI enrichment: active-cases / encounters / monitoring /
 * revenue tiles, with the sensitive high-risk tile gated by view_sensitive.
 */
class AdminScreensPhaseETest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'phaseE-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();

        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'psychiatry']);
        $patient = Patient::create(['full_name' => 'KPI Patient', 'phone' => '0500005555']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PE-1'])->save();
        NeuropsychEncounter::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'module' => 'psychiatry',
            'encounter_date' => now()->toDateString(), 'note_format' => 'soap',
        ]);
    }

    public function test_dashboard_exposes_enriched_kpis_for_sensitive_admin(): void
    {
        $this->actingAs($this->admin)->get('/admin/psychiatry')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Dashboard')
                ->where('canSeeSensitive', true)
                ->where('stats.active_cases', 1)
                ->has('stats.revenue_this_month')
                ->has('stats.monitoring_due')
            );
    }

    public function test_dashboard_hides_sensitive_flag_for_view_only_admin(): void
    {
        $this->admin->role->update(['permissions' => ['psychiatry.view']]);

        $this->actingAs($this->admin)->get('/admin/psychiatry')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Dashboard')
                ->where('canSeeSensitive', false)
                ->where('stats.active_cases', 1)
            );
    }
}

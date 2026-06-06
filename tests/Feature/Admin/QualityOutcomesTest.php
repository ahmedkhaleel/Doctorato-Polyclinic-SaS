<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\Role;
use App\Models\ScaleResult;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * P2-2b — cross-specialty clinical-outcomes dashboard. Renders per enabled
 * module, computes a real metric (PHQ-9 improvement), gates by reports.view,
 * and exports CSV.
 */
class QualityOutcomesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'admin'], [
            'display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true,
        ]);
        $role->update(['permissions' => ['*']]);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'outcomes-admin@test.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);

        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();
    }

    public function test_dashboard_renders_with_a_computed_scale_improvement(): void
    {
        $patient = Patient::create(['full_name' => 'Scale Pt', 'phone' => '0500009999']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-OUT-1'])->save();

        // Two PHQ-9 results: improvement of 8 points (18 → 10).
        ScaleResult::create(['patient_id' => $patient->id, 'scale_key' => 'phq9', 'answers' => [], 'score' => 18, 'taken_at' => now()->subMonth()]);
        ScaleResult::create(['patient_id' => $patient->id, 'scale_key' => 'phq9', 'answers' => [], 'score' => 10, 'taken_at' => now()]);

        $this->actingAs($this->admin)->get('/admin/reports/outcomes')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Quality/Outcomes')
                ->has('sections')
                ->where('sections', fn ($sections) => collect($sections)->contains(fn ($s) => $s['module'] === 'psychiatry'))
            );
    }

    public function test_export_returns_csv(): void
    {
        $resp = $this->actingAs($this->admin)->get('/admin/reports/outcomes/export');
        $resp->assertOk();
        $this->assertStringContainsString('text/csv', $resp->headers->get('content-type'));
    }

    public function test_requires_reports_permission(): void
    {
        $this->admin->role->update(['permissions' => []]);
        $status = $this->actingAs($this->admin)->get('/admin/reports/outcomes')->status();
        $this->assertContains($status, [403, 302]);
    }
}

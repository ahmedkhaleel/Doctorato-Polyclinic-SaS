<?php

namespace Tests\Feature\Physio;

use App\Models\Exercise;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PT-5 — admin oversight + permission gating for physiotherapy: super_admin
 * sees the pages, an admin without physiotherapy.view is blocked, settings
 * persist to module_settings, and the exercise catalog is CRUD-able.
 */
class PhysiotherapyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();
        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();
    }

    private function admin(array $permissions, string $roleName = 'admin'): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name_en' => ucfirst($roleName), 'display_name_ar' => $roleName, 'permissions' => $permissions, 'is_system' => true]);
        $role->update(['permissions' => $permissions]);

        return User::create(['name' => $roleName, 'email' => $roleName.'-pt@test.com', 'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true]);
    }

    public function test_super_admin_can_open_all_physio_admin_pages(): void
    {
        $user = $this->admin(['*'], 'super_admin');

        $this->actingAs($user)->get('/admin/physiotherapy')->assertOk();
        $this->actingAs($user)->get('/admin/physiotherapy/patients')->assertOk();
        $this->actingAs($user)->get('/admin/physiotherapy/exercises')->assertOk();
        $this->actingAs($user)->get('/admin/physiotherapy/settings')->assertOk();
    }

    public function test_admin_without_view_is_forbidden(): void
    {
        $user = $this->admin(['patients.view'], 'admin');
        $this->actingAs($user)->get('/admin/physiotherapy')->assertForbidden();
    }

    public function test_settings_update_persists_to_module_settings(): void
    {
        $user = $this->admin(['*'], 'super_admin');

        $this->actingAs($user)->post('/admin/physiotherapy/settings', [
            'consultation_fee' => 275, 'session_fee' => 210, 'session_commission' => 48, 'session_duration' => 50,
        ])->assertRedirect();

        $this->assertSame('275', DB::table('module_settings')->where('module', 'physiotherapy')->where('key', 'consultation_fee')->value('value'));
        $this->assertSame('210', DB::table('module_settings')->where('module', 'physiotherapy')->where('key', 'session_fee')->value('value'));
        $this->assertSame('commission', DB::table('module_settings')->where('module', 'physiotherapy')->where('key', 'session_commission')->value('group'));
    }

    public function test_admin_can_create_and_toggle_an_exercise(): void
    {
        $user = $this->admin(['*'], 'super_admin');

        $this->actingAs($user)->post('/admin/physiotherapy/exercises', [
            'name_en' => 'Wall slides', 'name_ar' => 'انزلاق الجدار', 'region' => 'shoulder', 'category' => 'mobility',
            'default_sets' => 3, 'default_reps' => 12,
        ])->assertRedirect();

        $ex = Exercise::where('name_en', 'Wall slides')->firstOrFail();
        $this->assertTrue($ex->is_active);

        $this->actingAs($user)->post("/admin/physiotherapy/exercises/{$ex->id}/toggle")->assertRedirect();
        $this->assertFalse($ex->fresh()->is_active);
    }

    public function test_secretary_overview_route_is_registered_and_admin_gated(): void
    {
        // The route exists and applies the secretary auth/module gate (not a 404).
        $this->get('/secretary/physiotherapy/overview')->assertRedirect(); // unauthenticated → login redirect
    }
}

<?php

namespace Tests\Feature\Obgyn;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin panel gating + control for the OB/GYN module:
 * super_admin sees everything; an admin without obgyn.view is blocked
 * (the "no tab without permission" rule); settings persist to module_settings.
 */
class ObgynAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions, string $roleName = 'admin'): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name_en' => ucfirst($roleName), 'display_name_ar' => $roleName, 'permissions' => $permissions, 'is_system' => true]
        );
        $role->update(['permissions' => $permissions]);

        return User::create([
            'name' => $roleName, 'email' => $roleName.'-ob@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    #[Test]
    public function super_admin_can_open_dashboard_reports_and_settings(): void
    {
        $user = $this->admin(['*'], 'super_admin');

        $this->actingAs($user)->get('/admin/obgyn')->assertOk();
        $this->actingAs($user)->get('/admin/obgyn/reports')->assertOk();
        $this->actingAs($user)->get('/admin/obgyn/settings')->assertOk();
    }

    #[Test]
    public function admin_without_obgyn_view_is_forbidden(): void
    {
        $user = $this->admin(['patients.view', 'visits.view'], 'admin');

        $this->actingAs($user)->get('/admin/obgyn')->assertForbidden();
    }

    #[Test]
    public function settings_update_persists_to_module_settings(): void
    {
        $user = $this->admin(['*'], 'super_admin');

        $this->actingAs($user)->post('/admin/obgyn/settings', [
            'anc_fee' => 175, 'ultrasound_fee' => 220, 'edd_alert_days' => 10,
        ])->assertRedirect();

        $this->assertSame('175', DB::table('module_settings')->where('module', 'obgyn')->where('key', 'anc_fee')->value('value'));
        $this->assertSame('220', DB::table('module_settings')->where('module', 'obgyn')->where('key', 'ultrasound_fee')->value('value'));
        $this->assertSame('10', DB::table('module_settings')->where('module', 'obgyn')->where('key', 'edd_alert_days')->value('value'));
    }
}

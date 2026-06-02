<?php

namespace Tests\Feature\Trial;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoTrialControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $roleName, array $perms, bool $demo = false): User
    {
        $role = Role::firstOrCreate(['name' => $roleName],
            ['display_name_en' => $roleName, 'display_name_ar' => $roleName, 'permissions' => $perms, 'is_system' => false]);

        return User::create([
            'name' => $roleName, 'email' => $roleName.uniqid().'@t.com', 'password' => Hash::make('DemoClinic@2026'),
            'role_id' => $role->id, 'is_active' => true, 'is_demo' => $demo,
            'trial_ends_at' => $demo ? now()->addDays(14) : null,
        ]);
    }

    public function test_super_admin_can_view_page(): void
    {
        $su = $this->makeUser('super_admin', ['*']);
        $this->makeUser('admin', ['*'], demo: true); // a demo account to list
        $this->actingAs($su)->get('/admin/demo-trial')->assertOk();
    }

    public function test_regular_admin_is_forbidden(): void
    {
        // settings.* permissions pass the route middleware, but the controller
        // hard-gates to super_admin → 403.
        $admin = $this->makeUser('admin', ['settings.view', 'settings.update']);
        $this->actingAs($admin)->get('/admin/demo-trial')->assertForbidden();
    }

    public function test_update_settings_persists(): void
    {
        $su = $this->makeUser('super_admin', ['*']);
        $this->actingAs($su)->post('/admin/demo-trial/settings', [
            'trial_days' => 30,
            'trial_contact_url' => 'https://doctorato.com/contact',
        ])->assertRedirect();

        $this->assertSame('30', Setting::get('trial_days'));
        $this->assertSame('https://doctorato.com/contact', Setting::get('trial_contact_url'));
    }

    public function test_extend_reactivates_and_pushes_expiry(): void
    {
        $su = $this->makeUser('super_admin', ['*']);
        $demo = $this->makeUser('admin', ['*'], demo: true);
        $demo->forceFill(['trial_ends_at' => now()->subDay(), 'is_active' => false])->save();

        $this->actingAs($su)->post('/admin/demo-trial/extend', ['days' => 20])->assertRedirect();

        $demo->refresh();
        $this->assertTrue($demo->is_active);
        $this->assertTrue($demo->trial_ends_at->isFuture());
        $this->assertFalse($demo->trialExpired());
    }

    public function test_reset_password_changes_demo_password(): void
    {
        $su = $this->makeUser('super_admin', ['*']);
        $demo = $this->makeUser('admin', ['*'], demo: true);
        $old = $demo->password;

        $this->actingAs($su)->post('/admin/demo-trial/reset-password', [
            'password' => 'NewDemoPass#99',
            'password_confirmation' => 'NewDemoPass#99',
        ])->assertRedirect();

        $demo->refresh();
        $this->assertNotSame($old, $demo->password);
        $this->assertTrue(Hash::check('NewDemoPass#99', $demo->password));
    }

    public function test_reset_password_validates_confirmation(): void
    {
        $su = $this->makeUser('super_admin', ['*']);
        $this->actingAs($su)->post('/admin/demo-trial/reset-password', [
            'password' => 'NewDemoPass#99',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }
}

<?php

namespace Tests\Feature\Demo;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    private function demoAdmin(): User
    {
        $role = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => ['*'], 'is_system' => false]);

        return User::create(['name' => 'D', 'email' => 'd'.uniqid().'@t.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true, 'is_demo' => true]);
    }

    private function realAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'SA', 'display_name_ar' => 'SA', 'permissions' => ['*'], 'is_system' => true]);

        return User::create(['name' => 'R', 'email' => 'r'.uniqid().'@t.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true, 'is_demo' => false]);
    }

    public function test_demo_user_can_view(): void
    {
        $this->actingAs($this->demoAdmin())->get('/admin')->assertOk();
    }

    public function test_demo_user_blocked_from_delete_routes(): void
    {
        $demo = $this->demoAdmin();
        // A representative destroy route (POST /delete pattern used across the app).
        $this->actingAs($demo)->post('/admin/patients/1/delete')
            ->assertRedirect(); // bounced back with error, not executed
        $this->assertTrue(session()->has('error'));
    }

    public function test_demo_user_blocked_from_settings_update(): void
    {
        $this->actingAs($this->demoAdmin())->post('/admin/settings', ['clinic_name' => 'Hacked'])
            ->assertRedirect();
        $this->assertTrue(session()->has('error'));
    }

    public function test_demo_user_can_post_a_normal_create(): void
    {
        // A create route should pass the guard (validation may then complain, but
        // the guard must NOT block it). We assert it is not the demo-block redirect.
        $resp = $this->actingAs($this->demoAdmin())->post('/admin/patients', [
            'full_name' => 'New Demo Patient', 'phone' => '01055667788', 'gender' => 'male', 'referral_source' => 'walk_in',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('patients', ['phone' => '01055667788']);
    }

    public function test_non_demo_user_is_not_restricted_by_guard(): void
    {
        // Real super_admin hitting settings update is allowed by the guard
        // (it may still be gated by permissions, but the guard adds no block).
        $resp = $this->actingAs($this->realAdmin())->post('/admin/settings', ['clinic_name' => 'Real Clinic']);
        // Not the demo block: no 'error' flash from the guard.
        $resp->assertRedirect();
        $this->assertFalse(session()->get('error') === 'Demo mode: changing core settings is disabled.');
    }

    public function test_demo_seeder_creates_four_accounts(): void
    {
        $this->seed(DemoUserSeeder::class);
        foreach (['demo.admin', 'demo.doctor', 'demo.secretary', 'demo.patient'] as $u) {
            $user = User::where('email', "{$u}@doctorato.net")->first();
            $this->assertNotNull($user, "{$u} should exist");
            $this->assertTrue($user->is_demo);
        }
        // Demo admin role: has create/view, never delete or settings.update.
        $perms = User::where('email', 'demo.admin@doctorato.net')->first()->role->permissions;
        $this->assertContains('patients.create', $perms);
        $this->assertNotContains('patients.delete', $perms);
        $this->assertNotContains('settings.update', $perms);
    }
}

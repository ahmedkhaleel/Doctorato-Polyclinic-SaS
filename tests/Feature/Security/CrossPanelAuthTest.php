<?php

namespace Tests\Feature\Security;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for a cross-panel session-leak we found in production:
 * a patient could authenticate at /patient/login and then load /admin/*
 * pages because AdminAuth used a blacklist of "known-foreign" roles
 * instead of a whitelist of allowed ones — so brand-new roles silently
 * passed through.
 *
 * AdminAuth was switched to an allowlist (['admin', 'super_admin']).
 * This test pins that behavior so it can't regress.
 */
class CrossPanelAuthTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            [
                'display_name_en' => ucfirst($roleName),
                'display_name_ar' => $roleName,
                'permissions'     => [],
                'is_system'       => true,
            ]
        );

        return User::create([
            'name'      => "Test {$roleName}",
            'email'     => $email,
            'password'  => bcrypt('password'),
            'role_id'   => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_panel_allows_admin_role(): void
    {
        $admin = $this->userWithRole('admin', 'admin@test.com');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200);
    }

    public function test_admin_panel_allows_super_admin_role(): void
    {
        $super = $this->userWithRole('super_admin', 'super@test.com');

        $this->actingAs($super)
            ->get('/admin')
            ->assertStatus(200);
    }

    public function test_admin_panel_redirects_patient_to_their_own_panel(): void
    {
        $user = $this->userWithRole('patient', 'patient-cross@test.com');
        Patient::create([
            'user_id'   => $user->id,
            'full_name' => 'Test Patient',
            'phone'     => '+971500000000',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(302);
        $response->assertRedirect('/patient');
    }

    public function test_admin_panel_redirects_doctor_to_their_own_panel(): void
    {
        $user = $this->userWithRole('doctor', 'doctor-cross@test.com');

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect('/doctor');
    }

    public function test_admin_panel_redirects_secretary_to_their_own_panel(): void
    {
        $user = $this->userWithRole('secretary', 'secretary-cross@test.com');

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect('/secretary');
    }

    public function test_admin_panel_redirects_unknown_role_to_login(): void
    {
        // A role not in the known-homes map still must not get through.
        $user = $this->userWithRole('marketing_assistant', 'marketing@test.com');

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_panel_redirects_unauthenticated_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_panel_logs_out_deactivated_accounts(): void
    {
        $admin = $this->userWithRole('admin', 'inactive-admin@test.com');
        $admin->update(['is_active' => false]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('admin.login'));

        // Session was destroyed, so next hit is unauthenticated.
        $this->assertGuest();
    }
}

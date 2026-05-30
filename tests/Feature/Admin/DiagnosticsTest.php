<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keeps /admin/diagnostics honest — it's read-only, but a bad refactor
 * could still expose secrets or break the admin-only gate.
 */
class DiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName, string $email, array $permissions = []): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            [
                'display_name_en' => ucfirst($roleName),
                'display_name_ar' => $roleName,
                'permissions' => $permissions,
                'is_system' => true,
            ]
        );
        $role->update(['permissions' => $permissions]);

        return User::create([
            'name' => "Test {$roleName}",
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_view_diagnostics(): void
    {
        $admin = $this->userWithRole('super_admin', 'super@test.com', ['settings.view']);

        $response = $this->actingAs($admin)->get('/admin/diagnostics');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Diagnostics')
            ->has('system.php_version')
            ->has('system.laravel_version')
            ->has('system.db_connected')
            ->has('telemedicine.blockers')
            ->has('scheduler.is_healthy')
            ->has('log_tail')
        );
    }

    public function test_patient_redirected_from_diagnostics(): void
    {
        $patient = $this->userWithRole('patient', 'p@test.com');

        // A non-admin hitting an admin page is bounced to the admin login by
        // the AdminAuth guard (it never leaks the patient session into admin).
        $this->actingAs($patient)
            ->get('/admin/diagnostics')
            ->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_redirected_to_login(): void
    {
        $this->get('/admin/diagnostics')
            ->assertRedirect(route('admin.login'));
    }

    public function test_diagnostics_response_does_not_leak_secrets(): void
    {
        $admin = $this->userWithRole('super_admin', 'super2@test.com', ['settings.view']);

        $content = $this->actingAs($admin)
            ->get('/admin/diagnostics')
            ->getContent();

        // Common secret-shaped strings that must never appear
        $forbidden = [
            'sk_live_', 'sk_test_',             // Stripe
            'AIza',                              // Google keys
            'DB_PASSWORD',
            env('DB_PASSWORD'),
        ];

        foreach ($forbidden as $needle) {
            if ($needle === '' || $needle === null) {
                continue;
            }
            $this->assertStringNotContainsString(
                $needle, $content,
                "Diagnostics response must not leak '{$needle}'"
            );
        }
    }
}

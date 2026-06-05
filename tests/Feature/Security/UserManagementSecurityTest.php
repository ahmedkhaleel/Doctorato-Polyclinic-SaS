<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * S3 + M1 — user management hardening: no plaintext password is flashed,
 * and a non-super-admin cannot mint/elevate a super_admin.
 */
class UserManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name_en' => $roleName, 'display_name_ar' => $roleName, 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);

        return User::create(['name' => $roleName, 'email' => $email, 'password' => bcrypt('secret123'), 'role_id' => $role->id, 'is_active' => true]);
    }

    public function test_password_is_not_flashed_to_session_on_create(): void
    {
        $admin = $this->makeAdmin('super_admin', 'sa@test.com');
        $staffRole = Role::firstOrCreate(['name' => 'secretary'], ['display_name_en' => 'Sec', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true]);

        $resp = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Staff', 'email' => 'staff@test.com',
            'password' => 'StrongPass1', 'password_confirmation' => 'StrongPass1',
            'role_id' => $staffRole->id, 'is_active' => true,
        ]);

        $resp->assertRedirect();
        $creds = session('credentials');
        $this->assertIsArray($creds);
        $this->assertArrayNotHasKey('password', $creds);   // S3: never flashed
        $this->assertDatabaseHas('users', ['email' => 'staff@test.com']);
    }

    public function test_non_super_admin_cannot_create_super_admin(): void
    {
        $admin = $this->makeAdmin('admin', 'adm@test.com');
        $superRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name_en' => 'SA', 'display_name_ar' => 'مدير عام', 'permissions' => ['*'], 'is_system' => true]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Evil', 'email' => 'evil@test.com',
            'password' => 'StrongPass1', 'password_confirmation' => 'StrongPass1',
            'role_id' => $superRole->id, 'is_active' => true,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'evil@test.com']);
    }

    public function test_super_admin_can_assign_super_admin(): void
    {
        $admin = $this->makeAdmin('super_admin', 'sa2@test.com');
        $superRole = Role::where('name', 'super_admin')->first();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Another SA', 'email' => 'sa3@test.com',
            'password' => 'StrongPass1', 'password_confirmation' => 'StrongPass1',
            'role_id' => $superRole->id, 'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'sa3@test.com']);
    }
}

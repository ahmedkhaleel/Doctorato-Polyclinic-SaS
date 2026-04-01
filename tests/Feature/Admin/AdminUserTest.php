<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name_en' => 'Super Admin', 'display_name_ar' => 'مدير عام', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-user-test@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->adminRole->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_users_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/users')->assertOk();
    }

    public function test_can_view_create_user_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/users/create')->assertOk();
    }

    public function test_can_create_user(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com']);
    }

    public function test_user_requires_email(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'No Email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->adminRole->id,
        ])->assertSessionHasErrors('email');
    }

    public function test_user_email_must_be_unique(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Duplicate',
            'email' => 'admin-user-test@test.com', // same as admin
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->adminRole->id,
        ])->assertSessionHasErrors('email');
    }

    public function test_password_requires_confirmation(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test',
            'email' => 'noconfirm@test.com',
            'password' => 'password123',
            'role_id' => $this->adminRole->id,
        ])->assertSessionHasErrors('password');
    }

    public function test_can_update_user(): void
    {
        $user = User::create([
            'name' => 'Edit Me', 'email' => 'editme@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->adminRole->id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'editme@test.com',
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_can_update_user_without_password(): void
    {
        $user = User::create([
            'name' => 'Keep Pass', 'email' => 'keeppass@test.com',
            'password' => bcrypt('oldpassword'), 'role_id' => $this->adminRole->id, 'is_active' => true,
        ]);

        $oldHash = $user->password;

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => 'Keep Pass Updated',
            'email' => 'keeppass@test.com',
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ])->assertRedirect();

        // Password should remain unchanged
        $user->refresh();
        $this->assertEquals($oldHash, $user->password);
    }

    public function test_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}")
            ->assertRedirect();

        // Admin should still exist
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_can_delete_other_user(): void
    {
        $user = User::create([
            'name' => 'Delete Me', 'email' => 'deleteme@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->adminRole->id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->delete("/admin/users/{$user->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}

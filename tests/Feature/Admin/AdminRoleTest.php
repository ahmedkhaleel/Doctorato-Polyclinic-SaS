<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-role-test@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->adminRole->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_roles_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/roles')->assertOk();
    }

    public function test_can_view_create_role_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/roles/create')->assertOk();
    }

    public function test_can_create_role(): void
    {
        $this->actingAs($this->admin)->post('/admin/roles', [
            'name' => 'test_role',
            'display_name_en' => 'Test Role',
            'display_name_ar' => 'دور تجريبي',
            'permissions' => ['patients.view', 'patients.create'],
        ])->assertRedirect();

        $this->assertDatabaseHas('roles', ['name' => 'test_role']);
    }

    public function test_role_name_must_be_lowercase_snake(): void
    {
        $this->actingAs($this->admin)->post('/admin/roles', [
            'name' => 'Invalid Role Name!',
            'display_name_en' => 'Test',
            'display_name_ar' => 'تجربة',
            'permissions' => ['patients.view'],
        ])->assertSessionHasErrors('name');
    }

    public function test_role_name_must_be_unique(): void
    {
        Role::create([
            'name' => 'existing_role',
            'display_name_en' => 'Existing',
            'display_name_ar' => 'موجود',
            'permissions' => ['patients.view'],
        ]);

        $this->actingAs($this->admin)->post('/admin/roles', [
            'name' => 'existing_role',
            'display_name_en' => 'Duplicate',
            'display_name_ar' => 'مكرر',
            'permissions' => ['patients.view'],
        ])->assertSessionHasErrors('name');
    }

    public function test_can_update_non_system_role(): void
    {
        $role = Role::create([
            'name' => 'editable_role',
            'display_name_en' => 'Editable',
            'display_name_ar' => 'قابل للتعديل',
            'permissions' => ['patients.view'],
        ]);

        $this->actingAs($this->admin)->post("/admin/roles/{$role->id}/update", [
            'display_name_en' => 'Updated Role',
            'display_name_ar' => 'دور محدث',
            'permissions' => ['patients.view', 'patients.create'],
        ])->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'display_name_en' => 'Updated Role']);
    }

    public function test_cannot_update_system_role(): void
    {
        $this->actingAs($this->admin)->post("/admin/roles/{$this->adminRole->id}/update", [
            'display_name_en' => 'Hacked Admin',
            'display_name_ar' => 'مخترق',
            'permissions' => ['patients.view'],
        ])->assertRedirect();

        // Should NOT have been changed
        $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id, 'display_name_en' => 'Super Admin']);
    }

    public function test_cannot_delete_system_role(): void
    {
        $this->actingAs($this->admin)->post("/admin/roles/{$this->adminRole->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id]);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $role = Role::create([
            'name' => 'has_users',
            'display_name_en' => 'Has Users',
            'display_name_ar' => 'مستخدمين',
            'permissions' => ['patients.view'],
        ]);

        User::create([
            'name' => 'Test User', 'email' => 'role-user@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/roles/{$role->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_can_delete_empty_non_system_role(): void
    {
        $role = Role::create([
            'name' => 'deletable_role',
            'display_name_en' => 'Deletable',
            'display_name_ar' => 'قابل للحذف',
            'permissions' => ['patients.view'],
        ]);

        $this->actingAs($this->admin)->post("/admin/roles/{$role->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}

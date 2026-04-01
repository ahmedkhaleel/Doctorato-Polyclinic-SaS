<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-dept@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_departments_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/departments')->assertOk();
    }

    public function test_can_create_department(): void
    {
        $this->actingAs($this->admin)->post('/admin/departments', [
            'name_ar' => 'الجلدية',
            'name_en' => 'Dermatology',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('departments', ['name_en' => 'Dermatology']);
    }

    public function test_department_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/departments', [
            'is_active' => true,
        ])->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_department(): void
    {
        $dept = Department::create(['name_ar' => 'قديم', 'name_en' => 'Old', 'is_active' => true]);

        $this->actingAs($this->admin)->post("/admin/departments/{$dept->id}", [
            'name_ar' => 'محدث',
            'name_en' => 'Updated',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('departments', ['id' => $dept->id, 'name_en' => 'Updated']);
    }

    public function test_can_delete_department(): void
    {
        $dept = Department::create(['name_ar' => 'حذف', 'name_en' => 'Delete', 'is_active' => true]);

        $this->actingAs($this->admin)->delete("/admin/departments/{$dept->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }
}

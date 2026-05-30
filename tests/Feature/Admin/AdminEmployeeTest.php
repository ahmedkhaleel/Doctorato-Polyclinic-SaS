<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEmployeeTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-emp@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_employees_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/employees')->assertOk();
    }

    public function test_can_view_create_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/employees/create')->assertOk();
    }

    public function test_can_create_employee(): void
    {
        $empUser = User::create([
            'name' => 'Staff Member', 'email' => 'staff@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->admin->role_id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/employees', [
            'user_id' => $empUser->id,
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'hire_date' => '2024-01-01',
            'job_title_ar' => 'موظف',
            'job_title_en' => 'Staff',
        ])->assertRedirect();

        $this->assertDatabaseHas('employees', ['user_id' => $empUser->id, 'basic_salary' => 5000]);
    }

    public function test_employee_requires_user(): void
    {
        $this->actingAs($this->admin)->post('/admin/employees', [
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_employee_requires_contract_type(): void
    {
        $empUser = User::create([
            'name' => 'Staff2', 'email' => 'staff2@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->admin->role_id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/employees', [
            'user_id' => $empUser->id,
            'basic_salary' => 5000,
        ])->assertSessionHasErrors('contract_type');
    }

    public function test_employee_requires_basic_salary(): void
    {
        $empUser = User::create([
            'name' => 'Staff3', 'email' => 'staff3@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->admin->role_id, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/employees', [
            'user_id' => $empUser->id,
            'contract_type' => 'full_time',
        ])->assertSessionHasErrors('basic_salary');
    }

    public function test_can_view_employee_show(): void
    {
        $empUser = User::create([
            'name' => 'Show Staff', 'email' => 'show-staff@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->admin->role_id, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $empUser->id,
            'employee_number' => 'EMP-0001',
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->get("/admin/employees/{$employee->id}")->assertOk();
    }

    public function test_can_update_employee(): void
    {
        $empUser = User::create([
            'name' => 'Update Staff', 'email' => 'upd-staff@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->admin->role_id, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $empUser->id,
            'employee_number' => 'EMP-0002',
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post("/admin/employees/{$employee->id}", [
            'contract_type' => 'part_time',
            'basic_salary' => 3000,
            'job_title_ar' => 'موظف جزئي',
            'job_title_en' => 'Part-time Staff',
        ])->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'basic_salary' => 3000]);
    }

    public function test_can_terminate_employee(): void
    {
        $empUser = User::create([
            'name' => 'Term Staff', 'email' => 'term-staff@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->admin->role_id, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $empUser->id,
            'employee_number' => 'EMP-0003',
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post("/admin/employees/{$employee->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'terminated']);
    }

    public function test_non_admin_cannot_access_employees(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'pat-emp@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/admin/employees');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}

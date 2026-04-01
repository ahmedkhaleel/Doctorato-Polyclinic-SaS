<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-leave@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $empRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتيرة', 'permissions' => [], 'is_system' => true]
        );

        $this->employee = User::create([
            'name' => 'Employee', 'email' => 'emp-leave@test.com',
            'password' => bcrypt('password'), 'role_id' => $empRole->id, 'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->employee->id,
            'employee_number' => 'EMP-LV-001',
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_leaves_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/leaves')->assertOk();
    }

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/leaves/create')->assertOk();
    }

    public function test_can_create_leave(): void
    {
        $this->actingAs($this->admin)->post('/admin/leaves', [
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-03',
            'reason' => 'Family vacation',
        ])->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $this->employee->id,
            'leave_type' => 'annual',
            'status' => 'pending',
        ]);
    }

    public function test_leave_requires_type_and_dates(): void
    {
        $this->actingAs($this->admin)->post('/admin/leaves', [])
            ->assertSessionHasErrors(['user_id', 'leave_type', 'start_date', 'end_date']);
    }

    public function test_end_date_must_be_after_start(): void
    {
        $this->actingAs($this->admin)->post('/admin/leaves', [
            'user_id' => $this->employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-04-05',
            'end_date' => '2026-04-03',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_can_approve_leave(): void
    {
        $leave = Leave::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-02',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->put("/admin/leaves/{$leave->id}", [
            'status' => 'approved',
        ])->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_can_reject_leave(): void
    {
        $leave = Leave::create([
            'user_id' => $this->employee->id,
            'leave_type' => 'personal',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-11',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->put("/admin/leaves/{$leave->id}", [
            'status' => 'rejected',
            'reason' => 'Insufficient staff coverage',
        ])->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'rejected',
        ]);
    }
}

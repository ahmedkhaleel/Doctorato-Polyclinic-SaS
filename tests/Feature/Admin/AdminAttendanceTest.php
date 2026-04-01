<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-att@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $empRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتيرة', 'permissions' => [], 'is_system' => true]
        );

        $this->employee = User::create([
            'name' => 'Employee', 'email' => 'emp-att@test.com',
            'password' => bcrypt('password'), 'role_id' => $empRole->id, 'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->employee->id,
            'employee_number' => 'EMP-ATT-001',
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_attendance_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/attendances')->assertOk();
    }

    public function test_can_create_attendance_record(): void
    {
        $this->actingAs($this->admin)->post('/admin/attendances', [
            'user_id' => $this->employee->id,
            'date' => '2026-03-30',
            'check_in' => '08:00',
            'check_out' => '16:00',
            'status' => 'present',
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->employee->id,
            'status' => 'present',
        ]);
    }

    public function test_attendance_requires_user_and_status(): void
    {
        $this->actingAs($this->admin)->post('/admin/attendances', [])
            ->assertSessionHasErrors(['user_id', 'date', 'status']);
    }

    public function test_invalid_status_rejected(): void
    {
        $this->actingAs($this->admin)->post('/admin/attendances', [
            'user_id' => $this->employee->id,
            'date' => '2026-03-30',
            'status' => 'half_day',
        ])->assertSessionHasErrors('status');
    }

    public function test_can_update_attendance(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-03-30',
            'status' => 'present',
            'check_in' => '08:00',
        ]);

        $this->actingAs($this->admin)->put("/admin/attendances/{$attendance->id}", [
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => 'late',
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'late',
        ]);
    }

    public function test_can_filter_by_status(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/attendances?status=present')
            ->assertOk();
    }
}

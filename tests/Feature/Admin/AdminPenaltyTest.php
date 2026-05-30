<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\Penalty;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPenaltyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-pen@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $empRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتيرة', 'permissions' => [], 'is_system' => true]
        );

        $empUser = User::create([
            'name' => 'Employee', 'email' => 'emp-pen@test.com',
            'password' => bcrypt('password'), 'role_id' => $empRole->id, 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $empUser->id,
            'employee_number' => 'EMP-PEN-001',
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_penalties_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/penalties')->assertOk();
    }

    public function test_can_create_penalty(): void
    {
        $this->actingAs($this->admin)->post('/admin/penalties', [
            'employee_id' => $this->employee->id,
            'type' => 'penalty',
            'amount' => 100,
            'reason' => 'Late arrival',
            'date' => '2026-03-30',
        ])->assertRedirect();

        $this->assertDatabaseHas('penalties', [
            'employee_id' => $this->employee->id,
            'type' => 'penalty',
            'amount' => 100,
        ]);
    }

    public function test_can_create_reward(): void
    {
        $this->actingAs($this->admin)->post('/admin/penalties', [
            'employee_id' => $this->employee->id,
            'type' => 'reward',
            'amount' => 500,
            'reason' => 'Outstanding performance',
            'date' => '2026-03-30',
        ])->assertRedirect();

        $this->assertDatabaseHas('penalties', [
            'employee_id' => $this->employee->id,
            'type' => 'reward',
            'amount' => 500,
        ]);
    }

    public function test_penalty_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/penalties', [])
            ->assertSessionHasErrors(['employee_id', 'type', 'amount', 'date']);
    }

    public function test_invalid_type_rejected(): void
    {
        $this->actingAs($this->admin)->post('/admin/penalties', [
            'employee_id' => $this->employee->id,
            'type' => 'warning',
            'amount' => 50,
            'date' => '2026-03-30',
        ])->assertSessionHasErrors('type');
    }

    public function test_can_delete_penalty(): void
    {
        $penalty = Penalty::create([
            'employee_id' => $this->employee->id,
            'type' => 'penalty',
            'amount' => 100,
            'date' => '2026-03-30',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/penalties/{$penalty->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('penalties', ['id' => $penalty->id]);
    }

    public function test_cannot_delete_applied_penalty(): void
    {
        $penalty = Penalty::create([
            'employee_id' => $this->employee->id,
            'type' => 'penalty',
            'amount' => 100,
            'date' => '2026-03-30',
            'created_by' => $this->admin->id,
            'applied_to_salary' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/penalties/{$penalty->id}/delete")->assertRedirect();
        // Should still exist because it was applied to salary
        $this->assertDatabaseHas('penalties', ['id' => $penalty->id]);
    }
}

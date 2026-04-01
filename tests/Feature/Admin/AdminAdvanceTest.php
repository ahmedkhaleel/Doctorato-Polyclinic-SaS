<?php

namespace Tests\Feature\Admin;

use App\Models\Advance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdvanceTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-adv@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $empRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتيرة', 'permissions' => [], 'is_system' => true]
        );

        $empUser = User::create([
            'name' => 'Employee', 'email' => 'emp-adv@test.com',
            'password' => bcrypt('password'), 'role_id' => $empRole->id, 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $empUser->id,
            'employee_number' => 'EMP-ADV-001',
            'contract_type' => 'full_time',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_advances_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/advances')->assertOk();
    }

    public function test_can_create_advance(): void
    {
        $this->actingAs($this->admin)->post('/admin/advances', [
            'employee_id' => $this->employee->id,
            'amount' => 2000,
            'monthly_installment' => 500,
            'reason' => 'Personal emergency',
        ])->assertRedirect();

        $this->assertDatabaseHas('advances', [
            'employee_id' => $this->employee->id,
            'amount' => 2000,
            'status' => 'pending',
        ]);
    }

    public function test_advance_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/advances', [])
            ->assertSessionHasErrors(['employee_id', 'amount', 'monthly_installment']);
    }

    public function test_amount_must_be_positive(): void
    {
        $this->actingAs($this->admin)->post('/admin/advances', [
            'employee_id' => $this->employee->id,
            'amount' => 0,
            'monthly_installment' => 100,
        ])->assertSessionHasErrors('amount');
    }

    public function test_can_approve_advance(): void
    {
        $advance = Advance::create([
            'employee_id' => $this->employee->id,
            'amount' => 1000,
            'monthly_installment' => 250,
            'remaining_balance' => 1000,
            'total_paid' => 0,
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/advances/{$advance->id}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('advances', [
            'id' => $advance->id,
            'status' => 'active',
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_can_reject_advance(): void
    {
        $advance = Advance::create([
            'employee_id' => $this->employee->id,
            'amount' => 1000,
            'monthly_installment' => 250,
            'remaining_balance' => 1000,
            'total_paid' => 0,
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/advances/{$advance->id}/reject")
            ->assertRedirect();

        $this->assertDatabaseHas('advances', [
            'id' => $advance->id,
            'status' => 'rejected',
        ]);
    }

    public function test_cannot_approve_non_pending_advance(): void
    {
        $advance = Advance::create([
            'employee_id' => $this->employee->id,
            'amount' => 1000,
            'monthly_installment' => 250,
            'remaining_balance' => 1000,
            'total_paid' => 0,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/advances/{$advance->id}/approve")
            ->assertRedirect();

        // Status should remain active, not change
        $this->assertDatabaseHas('advances', [
            'id' => $advance->id,
            'status' => 'active',
        ]);
    }
}

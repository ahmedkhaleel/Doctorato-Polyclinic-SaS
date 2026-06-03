<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\Role;
use App\Models\SalarySlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payroll is financially critical (salary slips, approve → mark-paid workflow,
 * bulk actions) and was previously untested. Covers the index, generation, the
 * draft→approved→paid state machine with its guards, and the bulk endpoints the
 * Payroll/Index UI posts to.
 */
class AdminPayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-payroll@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $empRole = Role::firstOrCreate(['name' => 'secretary'],
            ['display_name_en' => 'Sec', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true]);
        $empUser = User::create([
            'name' => 'Worker', 'email' => 'worker-payroll@test.com',
            'password' => bcrypt('password'), 'role_id' => $empRole->id, 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $empUser->id, 'employee_number' => 'EMP-PR-001',
            'contract_type' => 'full_time', 'basic_salary' => 6000, 'status' => 'active',
        ]);
    }

    private function makeSlip(string $status = 'draft', int $month = 3): SalarySlip
    {
        return SalarySlip::create([
            'slip_number' => SalarySlip::generateSlipNumber($month, 2026),
            'employee_id' => $this->employee->id,
            'month' => $month, 'year' => 2026,
            'basic_salary' => 6000, 'total_earnings' => 6000,
            'total_deductions' => 0, 'net_salary' => 6000,
            'status' => $status, 'created_by' => $this->admin->id,
        ]);
    }

    public function test_can_view_payroll_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/payroll')->assertOk();
    }

    public function test_can_generate_payroll_creates_slips(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/payroll/generate', ['month' => 4, 'year' => 2026])
            ->assertRedirect();

        $this->assertDatabaseHas('salary_slips', [
            'employee_id' => $this->employee->id, 'month' => 4, 'year' => 2026,
        ]);
    }

    public function test_generate_is_idempotent_per_period(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/payroll/generate', ['month' => 5, 'year' => 2026]);
        $this->post('/admin/payroll/generate', ['month' => 5, 'year' => 2026]);

        $this->assertSame(1, SalarySlip::where('employee_id', $this->employee->id)
            ->where('month', 5)->where('year', 2026)->count());
    }

    public function test_approve_then_mark_paid_workflow(): void
    {
        $slip = $this->makeSlip('draft');
        $this->actingAs($this->admin);

        $this->post("/admin/payroll/{$slip->id}/approve")->assertRedirect();
        $this->assertDatabaseHas('salary_slips', ['id' => $slip->id, 'status' => 'approved', 'approved_by' => $this->admin->id]);

        $this->post("/admin/payroll/{$slip->id}/mark-paid", ['payment_method' => 'cash'])->assertRedirect();
        $this->assertDatabaseHas('salary_slips', ['id' => $slip->id, 'status' => 'paid', 'paid_by' => $this->admin->id]);
    }

    public function test_cannot_mark_paid_before_approval(): void
    {
        $slip = $this->makeSlip('draft');
        $this->actingAs($this->admin)
            ->post("/admin/payroll/{$slip->id}/mark-paid")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('salary_slips', ['id' => $slip->id, 'status' => 'draft']);
    }

    public function test_bulk_approve_only_affects_drafts(): void
    {
        $draft = $this->makeSlip('draft', 3);
        $paid = $this->makeSlip('paid', 4);

        $this->actingAs($this->admin)
            ->post('/admin/payroll/bulk-approve', ['ids' => [$draft->id, $paid->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('salary_slips', ['id' => $draft->id, 'status' => 'approved']);
        $this->assertDatabaseHas('salary_slips', ['id' => $paid->id, 'status' => 'paid']); // unchanged
    }

    public function test_bulk_mark_paid_only_affects_approved(): void
    {
        $approved = $this->makeSlip('approved', 3);
        $draft = $this->makeSlip('draft', 4);

        $this->actingAs($this->admin)
            ->post('/admin/payroll/bulk-mark-paid', ['ids' => [$approved->id, $draft->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('salary_slips', ['id' => $approved->id, 'status' => 'paid']);
        $this->assertDatabaseHas('salary_slips', ['id' => $draft->id, 'status' => 'draft']); // unchanged
    }

    public function test_generate_validates_month_and_year(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/payroll/generate', ['month' => 13, 'year' => 1999])
            ->assertSessionHasErrors(['month', 'year']);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\DoctorPayout;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Doctor payouts are real cash disbursement (recorded in the expense ledger on
 * payment) with a draft→confirmed→paid state machine and a salary-mode guard
 * (salary doctors' commission goes on the payslip, not disbursed here). Was
 * previously untested.
 */
class AdminDoctorPayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Doctor $payoutDoctor;

    private Doctor $salaryDoctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-payout@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->payoutDoctor = Doctor::create([
            'name_ar' => 'د. عمولة', 'name_en' => 'Dr. Payout', 'status' => 'active',
            'payment_mode' => Doctor::PAY_PAYOUT, 'default_commission_percentage' => 50,
        ]);
        $this->salaryDoctor = Doctor::create([
            'name_ar' => 'د. راتب', 'name_en' => 'Dr. Salary', 'status' => 'active',
            'payment_mode' => Doctor::PAY_SALARY, 'default_commission_percentage' => 50,
        ]);
    }

    private function makePayout(Doctor $doctor, string $status = 'draft'): DoctorPayout
    {
        return DoctorPayout::create([
            'payout_number' => DoctorPayout::generatePayoutNumber(),
            'doctor_id' => $doctor->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'total_visits' => 10, 'total_revenue' => 5000, 'total_commission' => 2500,
            'deductions' => 0, 'net_amount' => 2500,
            'status' => $status, 'created_by' => $this->admin->id,
        ]);
    }

    public function test_can_view_payouts_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/doctor-payouts')->assertOk();
    }

    public function test_confirm_then_mark_paid_workflow(): void
    {
        $payout = $this->makePayout($this->payoutDoctor, 'draft');
        $this->actingAs($this->admin);

        $this->post("/admin/doctor-payouts/{$payout->id}/confirm")->assertRedirect();
        $this->assertDatabaseHas('doctor_payouts', ['id' => $payout->id, 'status' => 'confirmed']);

        $this->post("/admin/doctor-payouts/{$payout->id}/mark-paid", [
            'payment_method' => 'cash',
        ])->assertRedirect();
        $this->assertDatabaseHas('doctor_payouts', ['id' => $payout->id, 'status' => 'paid']);
    }

    public function test_cannot_mark_paid_before_confirm(): void
    {
        $payout = $this->makePayout($this->payoutDoctor, 'draft');
        $this->actingAs($this->admin)
            ->post("/admin/doctor-payouts/{$payout->id}/mark-paid", ['payment_method' => 'cash'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('doctor_payouts', ['id' => $payout->id, 'status' => 'draft']);
    }

    public function test_salary_mode_doctor_payout_cannot_be_paid(): void
    {
        $payout = $this->makePayout($this->salaryDoctor, 'confirmed');
        $this->actingAs($this->admin)
            ->post("/admin/doctor-payouts/{$payout->id}/mark-paid", ['payment_method' => 'cash'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('doctor_payouts', ['id' => $payout->id, 'status' => 'confirmed']);
    }

    public function test_mark_paid_requires_valid_payment_method(): void
    {
        $payout = $this->makePayout($this->payoutDoctor, 'confirmed');
        $this->actingAs($this->admin)
            ->post("/admin/doctor-payouts/{$payout->id}/mark-paid", ['payment_method' => 'bitcoin'])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_can_cancel_draft_payout(): void
    {
        $payout = $this->makePayout($this->payoutDoctor, 'draft');
        $this->actingAs($this->admin)
            ->post("/admin/doctor-payouts/{$payout->id}/cancel", ['cancellation_reason' => 'Created in error'])
            ->assertRedirect();

        $this->assertDatabaseHas('doctor_payouts', ['id' => $payout->id, 'status' => 'cancelled']);
    }
}

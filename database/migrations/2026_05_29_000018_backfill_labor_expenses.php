<?php

use App\Models\DoctorPayout;
use App\Models\SalarySlip;
use App\Services\LaborExpenseService;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill Expense records for salary slips / doctor payouts that were paid
 * BEFORE the labor→expense link existed, so historical financial reports
 * reflect the real labor cost. Each expense is dated at the original paid_at
 * (lands in the correct month). Idempotent — LaborExpenseService skips rows
 * already linked to an expense, so re-running books nothing extra.
 */
return new class extends Migration
{
    public function up(): void
    {
        $svc = app(LaborExpenseService::class);

        SalarySlip::where('status', 'paid')
            ->whereNull('expense_id')
            ->where('net_salary', '>', 0)
            ->chunkById(200, function ($slips) use ($svc) {
                foreach ($slips as $slip) {
                    $svc->recordForSalarySlip($slip);
                }
            });

        DoctorPayout::where('status', 'paid')
            ->whereNull('expense_id')
            ->where('net_amount', '>', 0)
            ->chunkById(200, function ($payouts) use ($svc) {
                foreach ($payouts as $payout) {
                    $svc->recordForDoctorPayout($payout);
                }
            });
    }

    public function down(): void
    {
        // Void only the expenses this backfill created (linked rows).
        $svc = app(LaborExpenseService::class);

        SalarySlip::whereNotNull('expense_id')->chunkById(200, function ($slips) use ($svc) {
            foreach ($slips as $slip) {
                $svc->reverse($slip);
            }
        });
        DoctorPayout::whereNotNull('expense_id')->chunkById(200, function ($payouts) use ($svc) {
            foreach ($payouts as $payout) {
                $svc->reverse($payout);
            }
        });
    }
};

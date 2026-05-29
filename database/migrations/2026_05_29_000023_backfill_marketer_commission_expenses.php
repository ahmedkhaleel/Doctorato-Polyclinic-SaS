<?php

use App\Models\MarketerCommission;
use App\Services\LaborExpenseService;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill Expense records for marketer commissions paid BEFORE the
 * commission→expense link existed, so historical sales-commission cost is
 * reflected in the financial reports. Idempotent (skips already-linked rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        $svc = app(LaborExpenseService::class);

        MarketerCommission::where('status', 'paid')
            ->whereNull('expense_id')
            ->where('commission_amount', '>', 0)
            ->chunkById(200, function ($commissions) use ($svc) {
                foreach ($commissions as $commission) {
                    $svc->recordForMarketerCommission($commission);
                }
            });
    }

    public function down(): void
    {
        $svc = app(LaborExpenseService::class);

        MarketerCommission::whereNotNull('expense_id')->chunkById(200, function ($commissions) use ($svc) {
            foreach ($commissions as $commission) {
                $svc->reverse($commission);
            }
        });
    }
};

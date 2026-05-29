<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a doctor is paid their commission — the hybrid model that prevents
 * double payment:
 *   - 'payout' (default): contractor — commission disbursed via Doctor
 *     Payouts (markPaid = cash out). Payroll never adds their commission.
 *   - 'salary': employee — commission is consolidated into the monthly
 *     salary slip; their payouts are calculation-only and cannot be
 *     cash-disbursed.
 *
 * Default 'payout' matches current reality (all doctors are contractors),
 * so this is a zero-impact, data-safe addition.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctors') && ! Schema::hasColumn('doctors', 'payment_mode')) {
            Schema::table('doctors', function (Blueprint $t) {
                $t->enum('payment_mode', ['payout', 'salary'])->default('payout')->after('default_commission_percentage');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'payment_mode')) {
            Schema::table('doctors', function (Blueprint $t) {
                $t->dropColumn('payment_mode');
            });
        }
    }
};

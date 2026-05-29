<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link the Expense booked when a marketer commission is paid, so sales
 * commission cost appears in the financial reports (real cash out, like
 * doctor payouts and salary slips) and can be voided if reversed.
 * Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketer_commissions') && ! Schema::hasColumn('marketer_commissions', 'expense_id')) {
            Schema::table('marketer_commissions', function (Blueprint $t) {
                $t->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketer_commissions') && Schema::hasColumn('marketer_commissions', 'expense_id')) {
            Schema::table('marketer_commissions', function (Blueprint $t) {
                $t->dropConstrainedForeignId('expense_id');
            });
        }
    }
};

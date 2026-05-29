<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link the Expense record created when a salary slip / doctor payout is paid,
 * so labor costs (the clinic's largest outflow) appear in the financial
 * reports — and so the expense can be voided precisely if the payment is
 * reversed. Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['salary_slips', 'doctor_payouts'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'expense_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['salary_slips', 'doctor_payouts'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'expense_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('expense_id');
                });
            }
        }
    }
};

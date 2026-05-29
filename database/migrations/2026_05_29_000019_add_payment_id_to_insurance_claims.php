<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link the Payment created when an insurer reimburses a claim, so the
 * reimbursement (real income) lands on the invoice and in revenue reports —
 * and so it can be kept in sync / voided as the claim's paid_amount changes.
 * Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_claims') && ! Schema::hasColumn('insurance_claims', 'payment_id')) {
            Schema::table('insurance_claims', function (Blueprint $t) {
                $t->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('insurance_claims') && Schema::hasColumn('insurance_claims', 'payment_id')) {
            Schema::table('insurance_claims', function (Blueprint $t) {
                $t->dropConstrainedForeignId('payment_id');
            });
        }
    }
};

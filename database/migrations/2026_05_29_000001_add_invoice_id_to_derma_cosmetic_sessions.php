<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link clinical sessions to the financial system.
 *
 * Before this, cosmetic_sessions / derma_sessions carried a free-floating
 * `cost` column that never touched Invoice/Payment — cosmetic revenue was a
 * parallel number invisible to the financial reports, the patient wallet and
 * the loyalty engine. This adds a nullable invoice_id so a completed session
 * can be billed through the same Invoice/InvoiceItem pipeline the dental
 * module already uses (see DentalInvoiceService).
 *
 * Idempotent + data-safe: only adds the column if missing, nulls on invoice
 * delete so deleting an invoice never cascades into clinical history.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cosmetic_sessions', 'derma_sessions'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'invoice_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('invoice_id')
                        ->nullable()
                        ->after('cost')
                        ->constrained('invoices')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['cosmetic_sessions', 'derma_sessions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'invoice_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('invoice_id');
                });
            }
        }
    }
};

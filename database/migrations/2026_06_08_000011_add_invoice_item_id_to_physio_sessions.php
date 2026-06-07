<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PT-2 — link a physio session back to the InvoiceItem it produced, so
 * PhysioBillingService can be idempotent/reversible exactly like
 * ObgynBillingService (which keys on `invoice_item_id`). Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('physio_sessions') || Schema::hasColumn('physio_sessions', 'invoice_item_id')) {
            return;
        }

        Schema::table('physio_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_item_id')->nullable()->after('invoice_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('physio_sessions') && Schema::hasColumn('physio_sessions', 'invoice_item_id')) {
            Schema::table('physio_sessions', function (Blueprint $table) {
                $table->dropColumn('invoice_item_id');
            });
        }
    }
};

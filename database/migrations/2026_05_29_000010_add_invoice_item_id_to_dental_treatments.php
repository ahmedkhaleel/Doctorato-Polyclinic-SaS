<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track the exact invoice line a dental treatment produced, so deleting or
 * un-completing the treatment can void precisely that line (and recalc the
 * invoice) instead of leaving phantom revenue. Lab orders already carry
 * invoice_item_id; this brings treatments in line. Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dental_treatments') && ! Schema::hasColumn('dental_treatments', 'invoice_item_id')) {
            Schema::table('dental_treatments', function (Blueprint $t) {
                $t->foreignId('invoice_item_id')->nullable()->after('invoice_id')
                    ->constrained('invoice_items')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dental_treatments') && Schema::hasColumn('dental_treatments', 'invoice_item_id')) {
            Schema::table('dental_treatments', function (Blueprint $t) {
                $t->dropConstrainedForeignId('invoice_item_id');
            });
        }
    }
};

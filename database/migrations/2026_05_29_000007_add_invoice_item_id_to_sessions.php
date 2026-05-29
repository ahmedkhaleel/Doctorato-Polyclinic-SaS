<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track the exact invoice line a session produced, so deleting or
 * un-completing a session can void precisely that line (and recalc the
 * invoice) instead of guessing. Mirrors how the dental module links
 * treatments to their invoice. Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cosmetic_sessions', 'derma_sessions'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'invoice_item_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('invoice_item_id')->nullable()->after('invoice_id')
                        ->constrained('invoice_items')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['cosmetic_sessions', 'derma_sessions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'invoice_item_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('invoice_item_id');
                });
            }
        }
    }
};

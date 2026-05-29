<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link cosmetic procedures/sessions to inventory so a performed procedure
 * draws down stock (fillers, injectables…). Hybrid model:
 *   - cosmetic_procedures.supply_id + default_consumption_qty → the default
 *     item/amount consumed per session (automatic).
 *   - cosmetic_sessions.supply_id + consumption_qty → optional per-session
 *     override (manual).
 *   - cosmetic_sessions.supply_transaction_id → links the SupplyTransaction
 *     created on completion, guaranteeing a session is never deducted twice.
 *
 * Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cosmetic_procedures')) {
            Schema::table('cosmetic_procedures', function (Blueprint $t) {
                if (! Schema::hasColumn('cosmetic_procedures', 'supply_id')) {
                    $t->foreignId('supply_id')->nullable()->after('default_price')
                        ->constrained('supplies')->nullOnDelete();
                }
                if (! Schema::hasColumn('cosmetic_procedures', 'default_consumption_qty')) {
                    $t->decimal('default_consumption_qty', 10, 2)->default(0)->after('supply_id');
                }
            });
        }

        if (Schema::hasTable('cosmetic_sessions')) {
            Schema::table('cosmetic_sessions', function (Blueprint $t) {
                if (! Schema::hasColumn('cosmetic_sessions', 'supply_id')) {
                    $t->foreignId('supply_id')->nullable()->after('procedure_id')
                        ->constrained('supplies')->nullOnDelete();
                }
                if (! Schema::hasColumn('cosmetic_sessions', 'consumption_qty')) {
                    $t->decimal('consumption_qty', 10, 2)->nullable()->after('supply_id');
                }
                if (! Schema::hasColumn('cosmetic_sessions', 'supply_transaction_id')) {
                    $t->foreignId('supply_transaction_id')->nullable()->after('consumption_qty')
                        ->constrained('supply_transactions')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cosmetic_sessions')) {
            Schema::table('cosmetic_sessions', function (Blueprint $t) {
                foreach (['supply_transaction_id', 'consumption_qty', 'supply_id'] as $col) {
                    if (Schema::hasColumn('cosmetic_sessions', $col)) {
                        if (in_array($col, ['supply_transaction_id', 'supply_id'])) {
                            $t->dropConstrainedForeignId($col);
                        } else {
                            $t->dropColumn($col);
                        }
                    }
                }
            });
        }
        if (Schema::hasTable('cosmetic_procedures')) {
            Schema::table('cosmetic_procedures', function (Blueprint $t) {
                if (Schema::hasColumn('cosmetic_procedures', 'default_consumption_qty')) {
                    $t->dropColumn('default_consumption_qty');
                }
                if (Schema::hasColumn('cosmetic_procedures', 'supply_id')) {
                    $t->dropConstrainedForeignId('supply_id');
                }
            });
        }
    }
};

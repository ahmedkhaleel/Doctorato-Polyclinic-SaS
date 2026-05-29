<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let a dental treatment draw its material from inventory (composite,
 * post, crown blank…). Per-treatment supply_id + consumption_qty (the
 * material/amount used); supply_transaction_id links the SupplyTransaction
 * created on completion so a treatment is never deducted twice. Mirrors the
 * cosmetic inventory link. Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dental_treatments')) {
            return;
        }
        Schema::table('dental_treatments', function (Blueprint $t) {
            if (! Schema::hasColumn('dental_treatments', 'supply_id')) {
                $t->foreignId('supply_id')->nullable()->after('lab_cost')
                    ->constrained('supplies')->nullOnDelete();
            }
            if (! Schema::hasColumn('dental_treatments', 'consumption_qty')) {
                $t->decimal('consumption_qty', 10, 2)->nullable()->after('supply_id');
            }
            if (! Schema::hasColumn('dental_treatments', 'supply_transaction_id')) {
                $t->foreignId('supply_transaction_id')->nullable()->after('consumption_qty')
                    ->constrained('supply_transactions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dental_treatments')) {
            return;
        }
        Schema::table('dental_treatments', function (Blueprint $t) {
            foreach (['supply_transaction_id', 'supply_id'] as $fk) {
                if (Schema::hasColumn('dental_treatments', $fk)) {
                    $t->dropConstrainedForeignId($fk);
                }
            }
            if (Schema::hasColumn('dental_treatments', 'consumption_qty')) {
                $t->dropColumn('consumption_qty');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory is per-branch, so the same product (same SKU / barcode) legitimately
 * exists in more than one branch. The global unique on supplies.sku / .barcode
 * would block that once branches are enabled. Make them branch-aware:
 * (branch_id, sku) and (branch_id, barcode). Idempotent via Schema::getIndexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplies') || ! Schema::hasColumn('supplies', 'branch_id')) {
            return;
        }

        $names = collect(Schema::getIndexes('supplies'))->pluck('name')->all();

        Schema::table('supplies', function (Blueprint $table) use ($names) {
            if (in_array('supplies_sku_unique', $names, true)) {
                $table->dropUnique('supplies_sku_unique');
            }
            if (! in_array('supplies_branch_sku_unique', $names, true)) {
                $table->unique(['branch_id', 'sku'], 'supplies_branch_sku_unique');
            }
            if (in_array('supplies_barcode_unique', $names, true)) {
                $table->dropUnique('supplies_barcode_unique');
            }
            if (! in_array('supplies_branch_barcode_unique', $names, true)) {
                $table->unique(['branch_id', 'barcode'], 'supplies_branch_barcode_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplies')) {
            return;
        }
        $names = collect(Schema::getIndexes('supplies'))->pluck('name')->all();
        Schema::table('supplies', function (Blueprint $table) use ($names) {
            if (in_array('supplies_branch_sku_unique', $names, true)) {
                $table->dropUnique('supplies_branch_sku_unique');
            }
            if (in_array('supplies_branch_barcode_unique', $names, true)) {
                $table->dropUnique('supplies_branch_barcode_unique');
            }
            if (! in_array('supplies_sku_unique', $names, true)) {
                $table->unique('sku', 'supplies_sku_unique');
            }
            if (! in_array('supplies_barcode_unique', $names, true)) {
                $table->unique('barcode', 'supplies_barcode_unique');
            }
        });
    }
};

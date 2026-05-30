<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link obstetric ultrasounds and deliveries to inventory, so their
 * consumables (gel, kits, delivery packs) are deducted from stock via a
 * SupplyTransaction — the same pattern cosmetic sessions use. Idempotent.
 */
return new class extends Migration
{
    private array $tables = ['obstetric_ultrasounds', 'delivery_records'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (! Schema::hasColumn($name, 'supply_id')) {
                    $table->foreignId('supply_id')->nullable()->constrained('supplies')->nullOnDelete();
                }
                if (! Schema::hasColumn($name, 'consumption_qty')) {
                    $table->decimal('consumption_qty', 10, 2)->nullable();
                }
                if (! Schema::hasColumn($name, 'supply_transaction_id')) {
                    $table->foreignId('supply_transaction_id')->nullable()->constrained('supply_transactions')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) use ($name) {
                foreach (['supply_id', 'consumption_qty', 'supply_transaction_id'] as $col) {
                    if (Schema::hasColumn($name, $col)) {
                        $table->dropConstrainedForeignId($col);
                    }
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let an administered vaccine draw a dose from inventory. supply_id links
 * the vaccine to a stock item; supply_transaction_id links the 'usage'
 * SupplyTransaction created when the dose is given, so it is never deducted
 * twice. Mirrors the dental/cosmetic inventory link. Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pediatric_vaccinations')) {
            return;
        }
        Schema::table('pediatric_vaccinations', function (Blueprint $t) {
            if (! Schema::hasColumn('pediatric_vaccinations', 'supply_id')) {
                $t->foreignId('supply_id')->nullable()->constrained('supplies')->nullOnDelete();
            }
            if (! Schema::hasColumn('pediatric_vaccinations', 'supply_transaction_id')) {
                $t->foreignId('supply_transaction_id')->nullable()->constrained('supply_transactions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pediatric_vaccinations')) {
            return;
        }
        Schema::table('pediatric_vaccinations', function (Blueprint $t) {
            foreach (['supply_transaction_id', 'supply_id'] as $fk) {
                if (Schema::hasColumn('pediatric_vaccinations', $fk)) {
                    $t->dropConstrainedForeignId($fk);
                }
            }
        });
    }
};

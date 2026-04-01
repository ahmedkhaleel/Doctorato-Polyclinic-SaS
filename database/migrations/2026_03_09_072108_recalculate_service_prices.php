<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recalculate service prices from supply_cost + medical_fee.
     *
     * The saving hook on the Service model computes price automatically,
     * but services created before the hook was added have NULL prices.
     */
    public function up(): void
    {
        DB::table('services')
            ->whereNotNull('medical_fee')
            ->update([
                'price' => DB::raw('COALESCE(supply_cost, 0) + medical_fee'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed — prices will be maintained by the model's saving hook
    }
};

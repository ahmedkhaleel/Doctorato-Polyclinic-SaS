<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('medical_fee', 10, 2)->nullable()->after('supply_cost');
        });

        // Backfill existing services: medical_fee = price - supply_cost
        DB::table('services')
            ->whereNotNull('price')
            ->update([
                'medical_fee' => DB::raw('COALESCE(price, 0) - COALESCE(supply_cost, 0)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('medical_fee');
        });
    }
};

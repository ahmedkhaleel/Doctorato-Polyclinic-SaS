<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('dental_consultation_fee', 10, 2)->nullable()->after('cosmetic_fee');
            $table->decimal('dental_service_fee', 10, 2)->nullable()->after('dental_consultation_fee');
            $table->decimal('dental_consultation_commission', 5, 2)->nullable()->after('followup_commission');
            $table->decimal('dental_service_commission', 5, 2)->nullable()->after('dental_consultation_commission');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn([
                'dental_consultation_fee',
                'dental_service_fee',
                'dental_consultation_commission',
                'dental_service_commission',
            ]);
        });
    }
};

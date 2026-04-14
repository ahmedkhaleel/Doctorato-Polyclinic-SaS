<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('pediatric_consultation_commission', 5, 2)->nullable()->after('dental_service_commission');
            $table->decimal('pediatric_followup_commission', 5, 2)->nullable()->after('pediatric_consultation_commission');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn([
                'pediatric_consultation_commission',
                'pediatric_followup_commission',
            ]);
        });
    }
};

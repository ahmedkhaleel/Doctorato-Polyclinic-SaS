<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add doctor_id to pediatric_allergies
        Schema::table('pediatric_allergies', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->after('patient_id')->constrained('doctors')->nullOnDelete();
        });

        // Add doctor_id to pediatric_chronic_conditions
        Schema::table('pediatric_chronic_conditions', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->after('patient_id')->constrained('doctors')->nullOnDelete();
        });

        // Add doctor_id to pediatric_nutrition_records
        Schema::table('pediatric_nutrition_records', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->after('patient_id')->constrained('doctors')->nullOnDelete();
        });

        // Add doctor_id to pediatric_family_history
        Schema::table('pediatric_family_history', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->after('patient_id')->constrained('doctors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pediatric_allergies', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });

        Schema::table('pediatric_chronic_conditions', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });

        Schema::table('pediatric_nutrition_records', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });

        Schema::table('pediatric_family_history', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });
    }
};

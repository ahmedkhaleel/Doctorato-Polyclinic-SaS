<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Guardian / Parent info
            $table->string('guardian_name')->nullable()->after('occupation');
            $table->string('guardian_relation')->nullable()->after('guardian_name');
            $table->string('guardian_phone')->nullable()->after('guardian_relation');
            $table->string('guardian_phone2')->nullable()->after('guardian_phone');
            $table->string('guardian_email')->nullable()->after('guardian_phone2');
            $table->string('guardian_occupation')->nullable()->after('guardian_email');

            // Birth History
            $table->enum('birth_type', ['normal', 'cesarean', 'vacuum', 'forceps'])->nullable()->after('guardian_occupation');
            $table->string('birth_place')->nullable()->after('birth_type');
            $table->decimal('gestational_age_weeks', 4, 1)->nullable()->after('birth_place');
            $table->decimal('birth_weight_kg', 5, 3)->nullable()->after('gestational_age_weeks');
            $table->decimal('birth_length_cm', 5, 1)->nullable()->after('birth_weight_kg');
            $table->decimal('birth_head_circumference_cm', 5, 1)->nullable()->after('birth_length_cm');
            $table->tinyInteger('apgar_1min')->nullable()->after('birth_head_circumference_cm');
            $table->tinyInteger('apgar_5min')->nullable()->after('apgar_1min');
            $table->json('birth_complications')->nullable()->after('apgar_5min');
            $table->integer('nicu_days')->nullable()->after('birth_complications');
            $table->json('newborn_screening')->nullable()->after('nicu_days');
            $table->enum('feeding_type', ['breastfed', 'formula', 'mixed'])->nullable()->after('newborn_screening');
            $table->text('pregnancy_complications')->nullable()->after('feeding_type');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_phone2',
                'guardian_email', 'guardian_occupation',
                'birth_type', 'birth_place', 'gestational_age_weeks',
                'birth_weight_kg', 'birth_length_cm', 'birth_head_circumference_cm',
                'apgar_1min', 'apgar_5min', 'birth_complications', 'nicu_days',
                'newborn_screening', 'feeding_type', 'pregnancy_complications',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Dental-specific medical history fields
            $table->boolean('has_dental_anxiety')->default(false)->after('medical_notes');
            $table->string('dental_anxiety_level', 20)->nullable()->after('has_dental_anxiety'); // none, mild, moderate, severe
            $table->text('previous_dental_surgeries')->nullable()->after('dental_anxiety_level');
            $table->boolean('latex_allergy')->default(false)->after('previous_dental_surgeries');
            $table->boolean('anesthesia_complications')->default(false)->after('latex_allergy');
            $table->text('anesthesia_notes')->nullable()->after('anesthesia_complications');
            $table->boolean('is_pregnant')->default(false)->after('anesthesia_notes');
            $table->boolean('is_breastfeeding')->default(false)->after('is_pregnant');
            $table->boolean('has_bleeding_disorder')->default(false)->after('is_breastfeeding');
            $table->boolean('takes_blood_thinners')->default(false)->after('has_bleeding_disorder');
            $table->string('blood_thinner_name')->nullable()->after('takes_blood_thinners');
            $table->boolean('has_heart_condition')->default(false)->after('blood_thinner_name');
            $table->boolean('has_diabetes')->default(false)->after('has_heart_condition');
            $table->string('diabetes_type', 20)->nullable()->after('has_diabetes'); // type_1, type_2, gestational
            $table->boolean('has_hepatitis')->default(false)->after('diabetes_type');
            $table->string('hepatitis_type', 10)->nullable()->after('has_hepatitis'); // A, B, C
            $table->boolean('has_hiv')->default(false)->after('hepatitis_type');
            $table->boolean('is_smoker')->default(false)->after('has_hiv');
            $table->string('smoking_frequency', 30)->nullable()->after('is_smoker'); // light, moderate, heavy, former
            $table->boolean('jaw_problems')->default(false)->after('smoking_frequency'); // TMJ/TMD
            $table->boolean('teeth_grinding')->default(false)->after('jaw_problems'); // bruxism
            $table->date('last_dental_visit')->nullable()->after('teeth_grinding');
            $table->text('dental_medical_notes')->nullable()->after('last_dental_visit');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'has_dental_anxiety', 'dental_anxiety_level', 'previous_dental_surgeries',
                'latex_allergy', 'anesthesia_complications', 'anesthesia_notes',
                'is_pregnant', 'is_breastfeeding',
                'has_bleeding_disorder', 'takes_blood_thinners', 'blood_thinner_name',
                'has_heart_condition', 'has_diabetes', 'diabetes_type',
                'has_hepatitis', 'hepatitis_type', 'has_hiv',
                'is_smoker', 'smoking_frequency',
                'jaw_problems', 'teeth_grinding',
                'last_dental_visit', 'dental_medical_notes',
            ]);
        });
    }
};

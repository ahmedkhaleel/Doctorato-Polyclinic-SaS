<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('treatment_type')->nullable()->index();
            $table->text('diagnosis_ar')->nullable();
            $table->text('diagnosis_en')->nullable();
            $table->text('notes_ar')->nullable();
            $table->text('notes_en')->nullable();
            $table->boolean('auto_apply')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('dental_prescription_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('dental_prescription_templates')->cascadeOnDelete();
            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('instructions_ar')->nullable();
            $table->string('instructions_en')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Link prescriptions to dental treatments (only if dental_treatments table exists)
        if (Schema::hasTable('dental_treatments') && !Schema::hasColumn('prescriptions', 'dental_treatment_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreignId('dental_treatment_id')->nullable()->after('doctor_id')
                    ->constrained('dental_treatments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('prescriptions', 'dental_treatment_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropForeign(['dental_treatment_id']);
                $table->dropColumn('dental_treatment_id');
            });
        }
        Schema::dropIfExists('dental_prescription_template_items');
        Schema::dropIfExists('dental_prescription_templates');
    }
};

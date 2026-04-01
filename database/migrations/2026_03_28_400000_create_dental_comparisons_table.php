<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('dental_treatment_plans')->nullOnDelete();

            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();

            // Category of comparison
            $table->enum('category', [
                'orthodontic',   // تقويم
                'cosmetic',      // تجميلي
                'implant',       // زراعة
                'whitening',     // تبييض
                'restoration',   // ترميم
                'surgical',      // جراحي
                'xray',          // أشعة
                'other',         // أخرى
            ])->default('other');

            // Before image
            $table->string('before_image_path');
            $table->date('before_date')->nullable();
            $table->text('before_notes')->nullable();

            // After image
            $table->string('after_image_path');
            $table->date('after_date')->nullable();
            $table->text('after_notes')->nullable();

            // Metadata
            $table->string('tooth_numbers')->nullable(); // comma-separated e.g. "11,12,21,22"
            $table->boolean('is_visible_to_patient')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['patient_id', 'category']);
            $table->index('is_visible_to_patient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_comparisons');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_well_child_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();

            $table->string('schedule_key', 10);          // e.g. '2m', '12m', '48m'
            $table->decimal('scheduled_age_months', 5, 1); // 0.25, 2, 12, 48, etc.
            $table->date('visit_date')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'missed', 'skipped'])->default('scheduled');

            // Measurements (optional — may reference growth records instead)
            $table->decimal('weight_kg', 6, 3)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('head_circumference_cm', 5, 1)->nullable();

            // Clinical notes
            $table->text('physical_exam_notes')->nullable();
            $table->text('development_notes')->nullable();
            $table->text('feeding_notes')->nullable();
            $table->text('safety_guidance')->nullable();

            // Tracking
            $table->json('vaccinations_given')->nullable();
            $table->json('screening_tests_done')->nullable();
            $table->json('referrals')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['patient_id', 'schedule_key']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_well_child_visits');
    }
};

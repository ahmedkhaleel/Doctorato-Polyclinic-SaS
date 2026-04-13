<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('vaccine_name');
            $table->string('vaccine_name_ar')->nullable();
            $table->string('dose_number');          // e.g. "Dose 1", "Booster"
            $table->string('scheduled_age');         // e.g. "2 months", "12 months"
            $table->date('scheduled_date')->nullable();
            $table->date('given_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('site_of_injection')->nullable(); // e.g. "Left thigh", "Right arm"
            $table->enum('status', ['scheduled', 'given', 'missed', 'postponed', 'contraindicated'])->default('scheduled');
            $table->text('side_effects')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_dose_date')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'vaccine_name']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_vaccinations');
    }
};

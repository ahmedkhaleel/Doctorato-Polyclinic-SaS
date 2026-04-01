<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            // ── Core Vitals ────────────────────────────────
            $table->decimal('blood_pressure_systolic', 5, 1)->nullable();   // mmHg
            $table->decimal('blood_pressure_diastolic', 5, 1)->nullable();  // mmHg
            $table->decimal('heart_rate', 5, 1)->nullable();                // bpm
            $table->decimal('temperature', 4, 1)->nullable();               // °C
            $table->decimal('respiratory_rate', 4, 1)->nullable();          // breaths/min
            $table->decimal('oxygen_saturation', 5, 2)->nullable();         // SpO2 %

            // ── Body Measurements ──────────────────────────
            $table->decimal('weight', 5, 1)->nullable();                    // kg
            $table->decimal('height', 5, 1)->nullable();                    // cm
            $table->decimal('bmi', 4, 1)->nullable();                       // auto-calculated

            // ── Blood Sugar (important for dental) ─────────
            $table->decimal('blood_sugar', 5, 1)->nullable();               // mg/dL
            $table->enum('blood_sugar_type', ['fasting', 'random', 'post_meal'])->nullable();

            // ── Pain Assessment ────────────────────────────
            $table->unsignedTinyInteger('pain_level')->nullable();           // 0-10 scale
            $table->string('pain_location')->nullable();

            // ── Meta ───────────────────────────────────────
            $table->text('notes')->nullable();
            $table->enum('source', ['manual', 'device', 'pre_visit'])->default('manual');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            // ── Indexes ────────────────────────────────────
            $table->index(['patient_id', 'recorded_at']);
            $table->index('visit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_vitals');
    }
};

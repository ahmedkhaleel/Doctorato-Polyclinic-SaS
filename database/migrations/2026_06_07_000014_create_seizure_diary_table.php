<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** NP5 — seizure diary. SHARED at patient level (longitudinal, patient-fillable). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seizure_diary')) {
            return;
        }
        Schema::create('seizure_diary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('occurred_at');
            $table->string('seizure_type', 40)->nullable(); // ILAE: focal_aware|focal_impaired|generalized_tonic_clonic|absence|myoclonic|other
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('triggers')->nullable();
            $table->string('entered_by', 12)->default('patient'); // patient | doctor
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seizure_diary');
    }
};

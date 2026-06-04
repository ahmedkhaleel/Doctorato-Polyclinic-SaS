<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP2 — measurement-based-care results. SHARED at patient level (longitudinal
 * measurement data follows the patient, like the profile → no branch_id).
 * Instrument definitions live in code (ScaleEngine); only answers + computed
 * score/severity are stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scale_results')) {
            return;
        }

        Schema::create('scale_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('scale_key', 32);              // phq9 | gad7 | hit6 | …
            $table->json('answers');                       // [itemIndex => chosenValue]
            $table->integer('score');
            $table->string('severity', 32)->nullable();    // English band label (canonical)
            $table->boolean('flag')->default(false);       // e.g. PHQ-9 suicidality
            $table->string('entered_by', 12)->default('doctor'); // doctor | patient
            $table->foreignId('neuropsych_encounter_id')->nullable()->constrained('neuropsych_encounters')->nullOnDelete();
            $table->dateTime('taken_at');
            $table->timestamps();

            $table->index(['patient_id', 'scale_key', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scale_results');
    }
};

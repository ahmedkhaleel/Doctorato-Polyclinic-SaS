<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP0.4 — baseline psychiatric/neurological profile, ONE row per patient,
 * SHARED across the psychiatry and neurology modules (mirrors obgyn_profiles:
 * patient-level clinical state → no branch_id, never branch-scoped).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neuropsych_profiles')) {
            return;
        }

        Schema::create('neuropsych_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->text('hpi')->nullable();                  // history of presenting illness
            $table->text('past_psych_history')->nullable();
            $table->text('past_neuro_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();
            $table->json('substance_history')->nullable();    // {tobacco, alcohol, drugs:[…]}
            $table->text('developmental_history')->nullable();
            $table->json('risk_factors')->nullable();         // standing flags
            $table->text('current_meds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neuropsych_profiles');
    }
};

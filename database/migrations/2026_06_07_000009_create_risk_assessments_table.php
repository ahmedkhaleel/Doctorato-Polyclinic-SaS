<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP3 — suicide / violence / self-harm risk assessments (e.g. C-SSRS). SHARED
 * at patient level (risk follows the patient, like the profile → no branch_id).
 * SENSITIVE: reads/writes are gated by *.view_sensitive and audited. A
 * moderate/high assessment must carry a safety plan (enforced in the service).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('risk_assessments')) {
            return;
        }

        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('type', 20)->default('suicide');   // suicide | violence | self_harm
            $table->string('tool', 20)->default('c-ssrs');
            $table->json('answers')->nullable();
            $table->string('risk_level', 12)->default('none'); // none | low | moderate | high
            $table->text('safety_plan')->nullable();
            $table->boolean('is_active')->default(true);       // cleared when resolved
            $table->foreignId('neuropsych_encounter_id')->nullable()->constrained('neuropsych_encounters')->nullOnDelete();
            $table->dateTime('assessed_at');
            $table->timestamps();

            $table->index(['patient_id', 'is_active', 'risk_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP1 — diagnoses attached to a neuropsych encounter. Supports both coding
 * systems used in the domain: ICD-11 (neurology + general) and DSM-5-TR
 * (psychiatry). Branch-scoped via its parent encounter.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('encounter_diagnoses')) {
            return;
        }

        Schema::create('encounter_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neuropsych_encounter_id')->constrained('neuropsych_encounters')->cascadeOnDelete();
            $table->string('code_system', 10)->default('icd11'); // icd11 | dsm5
            $table->string('code', 32)->nullable();
            $table->string('label');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('neuropsych_encounter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_diagnoses');
    }
};

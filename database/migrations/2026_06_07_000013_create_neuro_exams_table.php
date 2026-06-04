<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** NP5 — structured neurological examination, tied to an encounter (branch via parent). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neuro_exams')) {
            return;
        }
        Schema::create('neuro_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neuropsych_encounter_id')->constrained('neuropsych_encounters')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->json('cranial_nerves')->nullable();   // {i..xii}
            $table->json('motor')->nullable();
            $table->json('sensory')->nullable();
            $table->json('reflexes')->nullable();
            $table->string('coordination')->nullable();
            $table->string('gait')->nullable();
            $table->string('romberg')->nullable();         // negative | positive
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neuro_exams');
    }
};

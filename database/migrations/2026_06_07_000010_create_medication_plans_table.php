<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP4 — psychotropic / neuro medication plan. Branch-scoped clinical event.
 * `drug_class` drives automatic monitoring scheduling (clozapine, lithium,
 * antipsychotic → metabolic). `is_controlled` mirrors into the controlled-
 * substance register.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medication_plans')) {
            return;
        }

        Schema::create('medication_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('module', 20); // psychiatry | neurology
            $table->string('drug');
            $table->string('drug_class', 40)->nullable(); // clozapine|lithium|antipsychotic|ssri|aed|stimulant|benzodiazepine|other
            $table->string('dose')->nullable();
            $table->string('frequency')->nullable();
            $table->string('route', 20)->nullable();
            $table->date('started_at')->nullable();
            $table->date('stopped_at')->nullable();
            $table->boolean('is_controlled')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_plans');
    }
};

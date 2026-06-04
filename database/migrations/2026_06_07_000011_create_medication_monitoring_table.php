<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP4 — scheduled monitoring tasks for a medication plan (clozapine ANC,
 * lithium level, antipsychotic metabolic panel). Created automatically when a
 * plan is started; the clinician records a result when done.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medication_monitoring')) {
            return;
        }

        Schema::create('medication_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_plan_id')->constrained('medication_plans')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);          // clozapine_anc | lithium_level | metabolic
            $table->date('due_at');
            $table->string('result')->nullable();
            $table->date('result_at')->nullable();
            $table->string('status', 12)->default('due'); // due | done
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status', 'due_at']);
            $table->index('medication_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_monitoring');
    }
};

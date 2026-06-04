<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP1 — problem-oriented treatment plan for a neuropsych patient. Branch-scoped
 * clinical event. `items` holds problems→goals→interventions (json).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neuropsych_treatment_plans')) {
            return;
        }

        Schema::create('neuropsych_treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('module', 20); // psychiatry | neurology
            $table->string('title');
            $table->json('items')->nullable();        // [{problem, goal, intervention, target_date}]
            $table->string('status', 20)->default('active'); // active | achieved | discontinued
            $table->date('review_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'module', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neuropsych_treatment_plans');
    }
};

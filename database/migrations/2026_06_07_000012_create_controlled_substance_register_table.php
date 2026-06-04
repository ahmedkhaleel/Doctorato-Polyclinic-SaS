<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP4 — controlled-substance register (stimulants, benzodiazepines, …). MVP is
 * RECORD/TRACK ONLY — no automated e-prescribing (that is deferred,
 * NP-Future-1). An audited row is written whenever a controlled medication plan
 * is created. Branch-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('controlled_substance_register')) {
            return;
        }

        Schema::create('controlled_substance_register', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_plan_id')->nullable()->constrained('medication_plans')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('drug');
            $table->string('quantity')->nullable();
            $table->dateTime('prescribed_at');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();

            $table->index(['patient_id', 'prescribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_substance_register');
    }
};

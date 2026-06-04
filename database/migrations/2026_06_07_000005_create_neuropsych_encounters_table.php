<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP1 — clinical encounter for the psychiatry/neurology modules. This is a
 * clinical EVENT → branch-scoped (branch_id) + billing links, unlike the
 * patient-level shared profile. `module` discriminates psychiatry vs neurology.
 * `mse` holds the structured Mental Status Examination (json).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neuropsych_encounters')) {
            return;
        }

        Schema::create('neuropsych_encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('module', 20); // psychiatry | neurology
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->date('encounter_date');
            $table->string('note_format', 10)->default('soap'); // soap | dap | birp
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->json('mse')->nullable();          // structured Mental Status Examination
            $table->decimal('cost', 12, 2)->default(0);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->unsignedBigInteger('invoice_item_id')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'module']);
            $table->index(['branch_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neuropsych_encounters');
    }
};

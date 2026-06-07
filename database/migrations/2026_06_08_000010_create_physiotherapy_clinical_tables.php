<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PT-1 — physiotherapy clinical data layer. Idempotent (per-table hasTable
 * guard) + branch-aware (nullable branch_id + composite index) on the EVENT
 * tables; the exercise catalog is shared (no branch scope), per the project's
 * "templates/catalogs are shared" rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Assessment (the physio encounter) ───────────────────────
        if (! Schema::hasTable('physio_assessments')) {
            Schema::create('physio_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('visit_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->date('assessment_date')->nullable();
                $table->text('subjective')->nullable();
                $table->text('objective')->nullable();
                $table->string('icd10', 20)->nullable();
                $table->json('icf')->nullable();              // body fn/structure, activity, participation
                $table->json('red_flags')->nullable();        // precautions/contraindications
                $table->text('posture')->nullable();
                $table->text('gait')->nullable();
                $table->text('balance')->nullable();
                $table->json('special_tests')->nullable();
                $table->text('diagnosis')->nullable();
                $table->text('plan')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
                $table->index(['doctor_id', 'assessment_date']);
            });
        }

        // ── Treatment plan (goals + modalities + dosage) ─────────────
        if (! Schema::hasTable('physio_treatment_plans')) {
            Schema::create('physio_treatment_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('title_ar')->nullable();
                $table->string('title_en')->nullable();
                $table->text('problem_list')->nullable();
                $table->json('goals')->nullable();            // [{type,baseline,target,current,due}]
                $table->json('modalities')->nullable();       // ['tens','us','manual','exercise',...]
                $table->string('frequency', 40)->nullable();  // e.g. "3x/week"
                $table->unsignedSmallInteger('duration_weeks')->nullable();
                $table->unsignedSmallInteger('estimated_sessions')->default(0);
                $table->unsignedSmallInteger('completed_sessions')->default(0);
                $table->string('status', 20)->default('in_progress');
                $table->date('start_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
                $table->index(['doctor_id', 'status']);
            });
        }

        // ── Session (the billable treatment unit) ────────────────────
        if (! Schema::hasTable('physio_sessions')) {
            Schema::create('physio_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('treatment_plan_id')->nullable();
                $table->unsignedBigInteger('visit_id')->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->unsignedSmallInteger('session_number')->nullable();
                $table->date('session_date')->nullable();
                $table->text('soap')->nullable();
                $table->json('modalities')->nullable();       // [{type,params}]
                $table->text('techniques')->nullable();
                $table->text('exercises_done')->nullable();
                $table->boolean('attended')->default(true);
                $table->unsignedTinyInteger('pain_before')->nullable();
                $table->unsignedTinyInteger('pain_after')->nullable();
                $table->decimal('cost', 8, 2)->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
                $table->index(['treatment_plan_id']);
            });
        }

        // ── Pain map points (body chart) ─────────────────────────────
        if (! Schema::hasTable('physio_pain_points')) {
            Schema::create('physio_pain_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('assessment_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('view', 10)->default('front');
                $table->decimal('x', 5, 2);
                $table->decimal('y', 5, 2);
                $table->unsignedTinyInteger('intensity')->nullable(); // 0–10 NPRS
                $table->string('pain_type', 30)->nullable();          // sharp/dull/burning/...
                $table->string('note', 255)->nullable();
                $table->date('recorded_at')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
                $table->index(['patient_id', 'view']);
            });
        }

        // ── ROM (goniometry) — one row per joint/motion/side over time ─
        if (! Schema::hasTable('physio_rom_measurements')) {
            Schema::create('physio_rom_measurements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('assessment_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('joint', 40);
                $table->string('motion', 40);
                $table->string('side', 12)->default('right'); // left/right/bilateral
                $table->decimal('arom', 5, 1)->nullable();
                $table->decimal('prom', 5, 1)->nullable();
                $table->decimal('normal_ref', 5, 1)->nullable();
                $table->date('recorded_at')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
                $table->index(['patient_id', 'joint', 'motion']);
            });
        }

        // ── MMT (manual muscle testing, Oxford 0–5) ──────────────────
        if (! Schema::hasTable('physio_strength_tests')) {
            Schema::create('physio_strength_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('assessment_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('muscle_group', 60);
                $table->string('side', 12)->default('right');
                $table->unsignedTinyInteger('grade')->nullable(); // 0–5
                $table->date('recorded_at')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
            });
        }

        // ── Exercise catalog (SHARED — no branch scope) ──────────────
        if (! Schema::hasTable('exercises')) {
            Schema::create('exercises', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('region', 40)->nullable();
                $table->string('category', 40)->nullable();
                $table->string('media_path')->nullable();
                $table->text('instructions')->nullable();
                $table->unsignedSmallInteger('default_sets')->nullable();
                $table->unsignedSmallInteger('default_reps')->nullable();
                $table->unsignedSmallInteger('default_hold_sec')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('region');
            });
        }

        // ── Home Exercise Program prescription ───────────────────────
        if (! Schema::hasTable('physio_exercise_prescriptions')) {
            Schema::create('physio_exercise_prescriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('treatment_plan_id')->nullable();
                $table->unsignedBigInteger('exercise_id')->nullable();
                $table->unsignedSmallInteger('sets')->nullable();
                $table->unsignedSmallInteger('reps')->nullable();
                $table->unsignedSmallInteger('hold_sec')->nullable();
                $table->string('frequency', 40)->nullable();
                $table->string('resistance', 60)->nullable();
                $table->string('status', 20)->default('active');
                $table->date('prescribed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
                $table->index(['patient_id', 'status']);
            });
        }

        // ── HEP adherence log (patient-facing tracking) ──────────────
        if (! Schema::hasTable('hep_adherence_logs')) {
            Schema::create('hep_adherence_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('prescription_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->date('log_date');
                $table->boolean('done')->default(false);
                $table->unsignedTinyInteger('pain_after')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'hep_adherence_logs', 'physio_exercise_prescriptions', 'exercises',
            'physio_strength_tests', 'physio_rom_measurements', 'physio_pain_points',
            'physio_sessions', 'physio_treatment_plans', 'physio_assessments',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

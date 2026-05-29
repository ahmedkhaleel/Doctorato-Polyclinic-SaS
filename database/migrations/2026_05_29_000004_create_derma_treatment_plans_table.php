<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dermatology treatment plans (courses).
 *
 * A derma course bundles a planned number of sessions of one type (e.g. a
 * 6-session laser course) so progress can be tracked end-to-end, the way the
 * dental module tracks treatment plans. Derma sessions link to a plan via
 * treatment_plan_id and drive its completed_sessions / status.
 *
 * Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('derma_treatment_plans')) {
            Schema::create('derma_treatment_plans', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $t->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
                $t->foreignId('skin_condition_id')->nullable()->constrained('skin_conditions')->nullOnDelete();
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->string('title_ar');
                $t->string('title_en')->nullable();
                $t->enum('session_type', ['laser', 'peel', 'phototherapy', 'injection', 'cryotherapy', 'other'])->default('other');
                $t->unsignedInteger('estimated_sessions')->default(1);
                $t->unsignedInteger('completed_sessions')->default(0);
                $t->unsignedInteger('interval_days')->nullable();
                $t->decimal('estimated_cost', 12, 2)->default(0);
                $t->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
                $t->date('start_date')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->softDeletes();
                $t->index(['patient_id', 'status']);
            });
        }

        if (Schema::hasTable('derma_sessions') && ! Schema::hasColumn('derma_sessions', 'treatment_plan_id')) {
            Schema::table('derma_sessions', function (Blueprint $t) {
                $t->foreignId('treatment_plan_id')
                    ->nullable()
                    ->after('visit_id')
                    ->constrained('derma_treatment_plans')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('derma_sessions') && Schema::hasColumn('derma_sessions', 'treatment_plan_id')) {
            Schema::table('derma_sessions', function (Blueprint $t) {
                $t->dropConstrainedForeignId('treatment_plan_id');
            });
        }
        Schema::dropIfExists('derma_treatment_plans');
    }
};

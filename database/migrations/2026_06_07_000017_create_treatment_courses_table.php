<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP6 — a procedural treatment course (ECT/rTMS/ketamine) delivered as a series
 * of sessions. Branch-scoped. A course may require signed consent before any
 * session is performed (consent_signed_at gate).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('treatment_courses')) {
            return;
        }
        Schema::create('treatment_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('module', 20)->default('psychiatry');
            $table->string('type', 20); // ect | rtms | ketamine
            $table->unsignedSmallInteger('planned_sessions')->default(1);
            $table->decimal('session_fee', 12, 2)->default(0);
            $table->boolean('consent_required')->default(true);
            $table->dateTime('consent_signed_at')->nullable();
            $table->string('consent_signed_by', 12)->nullable(); // patient | guardian | doctor
            $table->string('status', 20)->default('active'); // active | completed | discontinued
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'module', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_courses');
    }
};

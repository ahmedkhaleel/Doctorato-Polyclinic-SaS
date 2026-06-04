<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** NP6 — one session within a treatment course. Branch-scoped; bills on completion. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_sessions')) {
            return;
        }
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_course_id')->constrained('treatment_courses')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('session_number')->default(1);
            $table->date('performed_at')->nullable();
            $table->json('parameters')->nullable();  // e.g. rTMS intensity, ketamine dose
            $table->decimal('cost', 12, 2)->default(0);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->unsignedBigInteger('invoice_item_id')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index('treatment_course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};

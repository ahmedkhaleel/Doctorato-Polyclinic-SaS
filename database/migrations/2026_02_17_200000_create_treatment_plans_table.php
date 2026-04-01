<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('goals')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        Schema::create('treatment_plan_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained();
            $table->integer('step_order')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sessions_required')->default(1);
            $table->integer('sessions_completed')->default(0);
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('visit_id')->nullable()->constrained();
            $table->timestamps();

            $table->index(['treatment_plan_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_steps');
        Schema::dropIfExists('treatment_plans');
    }
};

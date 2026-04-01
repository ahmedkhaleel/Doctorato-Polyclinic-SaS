<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dental Chart - Tooth-level records for each patient
        Schema::create('dental_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('tooth_number'); // 1-32 (FDI: 11-48)
            $table->string('condition', 50)->default('healthy'); // healthy, decayed, filled, missing, crown, bridge, implant, root_canal, extracted
            $table->json('surfaces')->nullable(); // ['mesial','distal','occlusal','buccal','lingual']
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('present'); // present, missing, implant
            $table->timestamps();

            $table->unique(['patient_id', 'tooth_number']);
            $table->index('condition');
        });

        // Dental Treatment Plans - Comprehensive treatment plans (must be before treatments due to FK)
        Schema::create('dental_treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->integer('estimated_sessions')->default(1);
            $table->integer('completed_sessions')->default(0);
            $table->string('priority', 20)->default('normal'); // urgent, high, normal, low
            $table->string('status', 30)->default('draft'); // draft, approved, in_progress, completed, cancelled
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        // Dental Treatments - Individual treatment records per tooth
        Schema::create('dental_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('dental_treatment_plans')->nullOnDelete();
            $table->tinyInteger('tooth_number')->nullable();
            $table->string('treatment_type', 100);
            $table->json('surfaces')->nullable();
            $table->text('description')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('lab_cost', 10, 2)->default(0);
            $table->string('status', 30)->default('planned');
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'tooth_number']);
            $table->index('treatment_type');
            $table->index('status');
        });

        // Periodontal Charts - Gum health measurements
        Schema::create('periodontal_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('exam_date');
            $table->tinyInteger('tooth_number');
            $table->json('probing_depths')->nullable(); // 6 measurements per tooth [MB, B, DB, ML, L, DL]
            $table->json('recession')->nullable(); // 6 measurements
            $table->json('bleeding_on_probing')->nullable(); // 6 boolean values
            $table->json('plaque_index')->nullable(); // 6 values
            $table->tinyInteger('mobility')->nullable(); // 0-3
            $table->tinyInteger('furcation')->nullable(); // 0-3
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'exam_date']);
            $table->unique(['patient_id', 'exam_date', 'tooth_number']);
        });

        // Dental X-rays
        Schema::create('dental_xrays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50); // panoramic, periapical, bitewing, cephalometric, cbct, occlusal
            $table->string('image_path');
            $table->string('tooth_number')->nullable(); // can be single tooth or range
            $table->text('findings')->nullable();
            $table->text('notes')->nullable();
            $table->date('taken_date');
            $table->timestamps();

            $table->index(['patient_id', 'type']);
        });

        // Dental Lab Orders
        Schema::create('dental_lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dental_treatment_id')->nullable()->constrained('dental_treatments')->nullOnDelete();
            $table->string('lab_name')->nullable();
            $table->string('order_number')->nullable();
            $table->string('item_type', 100); // crown, bridge, denture, retainer, aligner, veneer, implant_abutment, night_guard
            $table->string('tooth_number')->nullable();
            $table->string('shade')->nullable(); // A1, A2, A3, B1, etc.
            $table->string('material')->nullable(); // zirconia, porcelain, emax, metal, composite
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('patient_charge', 10, 2)->default(0);
            $table->string('status', 30)->default('ordered'); // ordered, in_production, ready, delivered, adjustment, completed
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_lab_orders');
        Schema::dropIfExists('dental_xrays');
        Schema::dropIfExists('periodontal_charts');
        Schema::dropIfExists('dental_treatments');
        Schema::dropIfExists('dental_treatment_plans');
        Schema::dropIfExists('dental_charts');
    }
};

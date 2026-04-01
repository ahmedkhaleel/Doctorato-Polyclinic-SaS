<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_treatment_plan_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('category')->default('general'); // orthodontic, implant, cosmetic, restorative, surgical, periodontal, general
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->json('treatments'); // [{treatment_type, tooth_number, surfaces, description, cost, lab_cost, sort_order}]
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->integer('estimated_sessions')->default(1);
            $table->string('priority')->default('normal');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_treatment_plan_templates');
    }
};

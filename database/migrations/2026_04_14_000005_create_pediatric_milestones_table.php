<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('assessment_date');
            $table->decimal('age_months', 6, 2);
            $table->enum('category', ['gross_motor', 'fine_motor', 'language', 'social']);
            $table->string('milestone_key');        // e.g. "holds_head", "walks", "first_words"
            $table->string('milestone_name_en');
            $table->string('milestone_name_ar');
            $table->string('expected_age');          // e.g. "2 months"
            $table->enum('status', ['achieved', 'not_achieved', 'emerging'])->default('not_achieved');
            $table->date('achieved_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'category']);
            $table->unique(['patient_id', 'milestone_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_milestones');
    }
};

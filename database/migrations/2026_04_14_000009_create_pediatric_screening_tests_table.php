<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_screening_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('test_date');
            $table->decimal('age_months', 6, 2);
            $table->enum('test_type', ['mchat', 'vanderbilt_parent', 'vanderbilt_teacher', 'phq_a', 'vision', 'hearing']);
            $table->json('answers')->nullable();
            $table->integer('total_score')->nullable();
            $table->enum('result', ['normal', 'low_risk', 'medium_risk', 'high_risk', 'positive', 'negative'])->nullable();
            $table->text('interpretation')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'test_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_screening_tests');
    }
};

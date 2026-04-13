<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_growth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('measurement_date');
            $table->decimal('age_months', 6, 2);
            $table->decimal('weight_kg', 6, 3)->nullable();
            $table->decimal('height_cm', 6, 1)->nullable();
            $table->decimal('head_circumference_cm', 5, 1)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            // WHO Percentiles (calculated)
            $table->decimal('weight_percentile', 5, 2)->nullable();
            $table->decimal('height_percentile', 5, 2)->nullable();
            $table->decimal('head_percentile', 5, 2)->nullable();
            $table->decimal('bmi_percentile', 5, 2)->nullable();
            // Z-scores
            $table->decimal('weight_zscore', 5, 2)->nullable();
            $table->decimal('height_zscore', 5, 2)->nullable();
            $table->decimal('head_zscore', 5, 2)->nullable();
            $table->decimal('bmi_zscore', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'measurement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_growth_records');
    }
};

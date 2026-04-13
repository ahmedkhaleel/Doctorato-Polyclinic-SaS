<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_chronic_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->enum('condition_type', ['asthma', 'diabetes_type1', 'epilepsy', 'congenital_heart', 'anemia', 'kidney', 'genetic', 'other']);
            $table->string('condition_name');
            $table->string('condition_name_ar')->nullable();
            $table->date('diagnosed_date')->nullable();
            $table->enum('severity', ['mild', 'moderate', 'severe'])->nullable();
            $table->json('current_medications')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('action_plan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_chronic_conditions');
    }
};

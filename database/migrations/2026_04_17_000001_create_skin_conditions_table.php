<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skin_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('category', ['acne', 'psoriasis', 'eczema', 'melasma', 'vitiligo', 'rosacea', 'dermatitis', 'fungal', 'other'])->default('other');
            $table->enum('severity', ['mild', 'moderate', 'severe'])->default('mild');
            $table->string('body_area')->nullable();
            $table->date('diagnosed_at')->nullable();
            $table->enum('status', ['active', 'resolved', 'monitoring'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skin_conditions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosmetic_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('cosmetic_packages')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('cosmetic_procedures')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->unsignedInteger('session_number')->default(1);
            $table->string('area_treated')->nullable();
            $table->string('product_used')->nullable();
            $table->decimal('dose_units', 10, 2)->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'procedure_id']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosmetic_sessions');
    }
};

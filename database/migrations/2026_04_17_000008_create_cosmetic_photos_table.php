<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosmetic_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('cosmetic_sessions')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('cosmetic_procedures')->nullOnDelete();
            $table->enum('category', ['before', 'after', 'progress'])->default('progress');
            $table->string('body_area')->nullable();
            $table->date('taken_at')->nullable();
            $table->string('image_path');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosmetic_photos');
    }
};

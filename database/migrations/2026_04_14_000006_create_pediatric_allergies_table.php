<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->enum('allergy_type', ['food', 'drug', 'environmental', 'insect', 'other']);
            $table->string('allergen');
            $table->enum('severity', ['mild', 'moderate', 'severe', 'anaphylaxis']);
            $table->json('symptoms')->nullable();    // ["rash", "swelling", "breathing_difficulty"]
            $table->date('discovered_date')->nullable();
            $table->string('treatment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_allergies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derma_prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('condition_category')->nullable();
            $table->text('diagnosis_ar')->nullable();
            $table->text('diagnosis_en')->nullable();
            $table->json('items')->nullable(); // [{medication, dosage, frequency, duration, instructions}]
            $table->text('notes_ar')->nullable();
            $table->text('notes_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derma_prescription_templates');
    }
};

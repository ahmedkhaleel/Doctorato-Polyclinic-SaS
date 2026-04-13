<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_family_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('condition');             // e.g. "diabetes", "asthma", "heart_disease"
            $table->string('condition_ar')->nullable();
            $table->json('affected_members');         // ["father", "mother", "sibling", "grandparent"]
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_family_history');
    }
};

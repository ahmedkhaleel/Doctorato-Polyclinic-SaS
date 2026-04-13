<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_nutrition_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('age_months', 6, 2);
            // Infant feeding (0-24 months)
            $table->enum('feeding_type', ['breastfed', 'formula', 'mixed'])->nullable();
            $table->integer('feeds_per_day')->nullable();
            $table->integer('feed_duration_min')->nullable();
            $table->string('formula_brand')->nullable();
            $table->integer('formula_ml_per_feed')->nullable();
            $table->decimal('complementary_start_age', 4, 1)->nullable();
            $table->json('introduced_foods')->nullable();
            // Older child nutrition (2+)
            $table->integer('meals_per_day')->nullable();
            $table->enum('diet_quality', ['varied', 'limited', 'vegetarian', 'vegan'])->nullable();
            $table->enum('milk_intake', ['daily', 'sometimes', 'rarely', 'none'])->nullable();
            $table->enum('fruits_vegetables', ['daily', 'sometimes', 'rarely'])->nullable();
            $table->enum('fast_food', ['daily', 'weekly', 'rarely', 'never'])->nullable();
            $table->json('supplements')->nullable();  // ["vitamin_d", "iron", "omega3"]
            $table->json('food_allergies')->nullable();
            $table->json('eating_problems')->nullable(); // ["picky", "refusal", "poor_appetite"]
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_nutrition_records');
    }
};

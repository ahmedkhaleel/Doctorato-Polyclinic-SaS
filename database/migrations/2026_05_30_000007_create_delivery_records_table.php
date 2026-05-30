<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_records')) {
            return;
        }

        Schema::create('delivery_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('delivery_date');
            $table->string('delivery_mode', 20);                   // nvd / cesarean / instrumental
            $table->string('place')->nullable();
            $table->decimal('gestational_age_at_delivery', 4, 1)->nullable();
            $table->string('outcome', 20)->default('live');        // live / stillbirth
            $table->unsignedInteger('baby_weight_grams')->nullable();
            $table->string('baby_sex', 10)->nullable();
            $table->unsignedTinyInteger('apgar_1')->nullable();
            $table->unsignedTinyInteger('apgar_5')->nullable();
            $table->text('complications')->nullable();
            // Cross-module link: the newborn registered as a pediatric patient
            $table->foreignId('newborn_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('pregnancy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_records');
    }
};

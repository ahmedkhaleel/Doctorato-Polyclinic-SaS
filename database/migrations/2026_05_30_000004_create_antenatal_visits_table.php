<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('antenatal_visits')) {
            return;
        }

        Schema::create('antenatal_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('visit_date');
            $table->string('phase', 12)->default('antenatal');     // antenatal / postnatal
            $table->decimal('gestational_age_weeks', 4, 1)->nullable();
            $table->decimal('weight_kg', 6, 3)->nullable();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->decimal('fundal_height_cm', 5, 1)->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->string('presentation')->nullable();            // cephalic / breech / transverse
            $table->boolean('edema')->default(false);
            $table->string('urine_protein')->nullable();
            $table->string('urine_glucose')->nullable();
            $table->text('complaints')->nullable();
            $table->text('plan')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pregnancy_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antenatal_visits');
    }
};

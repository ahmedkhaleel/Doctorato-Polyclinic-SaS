<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CV-3 — derma body lesion map: a point placed on a body silhouette (front/back)
 * at normalized 0–100 coordinates, capturing lesion type/size per patient/visit.
 * Idempotent + branch-aware (nullable branch_id + composite index).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('derma_lesions')) {
            return;
        }

        Schema::create('derma_lesions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('view', 10)->default('front'); // front | back
            $table->decimal('x', 5, 2); // 0–100 (% of width)
            $table->decimal('y', 5, 2); // 0–100 (% of height)
            $table->string('lesion_type', 40)->nullable();
            $table->decimal('size_mm', 6, 1)->nullable();
            $table->string('note', 255)->nullable();
            $table->date('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'branch_id']);
            $table->index(['patient_id', 'view']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derma_lesions');
    }
};

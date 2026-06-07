<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CV (dental) — periodontal chart: current pocket-depth status per tooth, 6 sites
 * (mesio/mid/disto × buccal/lingual), bleeding-on-probing sites, and mobility.
 * One current row per (patient, tooth) — upserted on each charting. Idempotent +
 * branch-aware (nullable branch_id + composite index).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perio_measurements')) {
            return;
        }

        Schema::create('perio_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedSmallInteger('tooth_number');
            // pocket depths (mm) per site: mesio/mid/disto × buccal/lingual
            $table->unsignedTinyInteger('pd_mb')->nullable();
            $table->unsignedTinyInteger('pd_b')->nullable();
            $table->unsignedTinyInteger('pd_db')->nullable();
            $table->unsignedTinyInteger('pd_ml')->nullable();
            $table->unsignedTinyInteger('pd_l')->nullable();
            $table->unsignedTinyInteger('pd_dl')->nullable();
            $table->json('bop')->nullable();             // bleeding-on-probing site keys
            $table->unsignedTinyInteger('mobility')->nullable(); // 0–3
            $table->date('recorded_at')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'tooth_number']);
            $table->index(['patient_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perio_measurements');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_satisfactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // ── Ratings (1-5 stars) ─────────────────────────
            $table->unsignedTinyInteger('overall_rating');                    // Required
            $table->unsignedTinyInteger('doctor_rating')->nullable();
            $table->unsignedTinyInteger('staff_rating')->nullable();
            $table->unsignedTinyInteger('cleanliness_rating')->nullable();
            $table->unsignedTinyInteger('waiting_time_rating')->nullable();
            $table->unsignedTinyInteger('communication_rating')->nullable();

            // ── Feedback ────────────────────────────────────
            $table->text('comments')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->json('improvement_areas')->nullable();                   // Array: ['waiting_time', 'staff', 'facilities', ...]

            // ── NPS (Net Promoter Score) ────────────────────
            $table->unsignedTinyInteger('nps_score')->nullable();            // 0-10

            // ── Meta ────────────────────────────────────────
            $table->enum('source', ['sms', 'portal', 'tablet', 'admin'])->default('portal');
            $table->string('token')->unique()->nullable();                   // For SMS survey links
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
            $table->index(['doctor_id', 'created_at']);
            $table->index('overall_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_satisfactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('booking_service_id')->constrained('booking_services')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('session_number')->default(1);
            $table->enum('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'appointment_date', 'start_time']);
            $table->index(['booking_id', 'status']);
            $table->index(['appointment_date', 'status']);
            $table->unique(['doctor_id', 'appointment_date', 'start_time'], 'unique_doctor_timeslot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_appointments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('consultation_number')->unique();

            // Parties
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();

            // Links to existing domain objects
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            // Schedule
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');

            // Classification
            $table->string('module')->default('derma')->comment('derma, dental, pediatric, etc');
            $table->enum('consultation_type', ['initial', 'followup', 'second_opinion'])->default('initial');

            // Agora RTC
            $table->string('agora_channel_name')->unique();
            $table->text('agora_doctor_token')->nullable();
            $table->text('agora_patient_token')->nullable();
            $table->timestamp('agora_tokens_expire_at')->nullable();

            // Attendance / timing
            $table->timestamp('doctor_joined_at')->nullable();
            $table->timestamp('patient_joined_at')->nullable();
            $table->timestamp('session_started_at')->nullable();
            $table->timestamp('session_ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Status
            $table->enum('status', [
                'scheduled',
                'waiting',
                'in_progress',
                'completed',
                'missed_patient',
                'missed_doctor',
                'cancelled',
                'refunded',
                'technical_issue',
            ])->default('scheduled');

            // Clinical
            $table->text('chief_complaint')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('doctor_notes')->nullable();

            // Feedback
            $table->text('patient_feedback')->nullable();
            $table->tinyInteger('patient_rating')->nullable();

            // Recording
            $table->boolean('recording_enabled')->default(false);
            $table->string('recording_url', 500)->nullable();
            $table->string('recording_resource_id')->nullable();

            // Financials
            $table->decimal('fee', 10, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'refunded', 'failed'])->default('unpaid');
            $table->string('payment_gateway_reference')->nullable();

            // Cancellation
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'scheduled_date']);
            $table->index(['status', 'scheduled_date']);
            $table->index('scheduled_date');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_consultations');
    }
};

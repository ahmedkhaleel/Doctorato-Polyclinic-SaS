<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_smart_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();

            // Notification type
            $table->string('type'); // followup_reminder, lab_order_ready, stalled_plan_reminder, post_treatment_checkup
            $table->string('channel')->default('sms'); // sms, database, both

            // Reference to the source entity
            $table->string('notifiable_type')->nullable(); // DentalTreatment, DentalLabOrder, DentalTreatmentPlan
            $table->unsignedBigInteger('notifiable_id')->nullable();

            // Message content
            $table->text('message_ar');
            $table->text('message_en')->nullable();

            // Delivery status
            $table->string('status')->default('pending'); // pending, sent, delivered, failed, cancelled
            $table->timestamp('scheduled_at')->nullable(); // When should it be sent
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();

            // SMS metadata
            $table->string('phone')->nullable();
            $table->string('sms_provider')->nullable();

            // Smart timing
            $table->integer('delay_hours')->default(0); // Delay after trigger event
            $table->boolean('is_auto')->default(true); // Auto-generated vs manual
            $table->boolean('patient_responded')->default(false); // Did patient respond/act?

            // Prevent duplicates
            $table->string('dedup_key')->nullable()->index(); // Unique key to prevent duplicate sends

            $table->timestamps();

            $table->index(['patient_id', 'type']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_smart_notifications');
    }
};

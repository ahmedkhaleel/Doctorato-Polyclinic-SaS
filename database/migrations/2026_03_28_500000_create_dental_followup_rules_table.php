<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_followup_rules', function (Blueprint $table) {
            $table->id();
            $table->string('treatment_type');         // e.g. filling, implant, root_canal
            $table->string('label_ar');               // Arabic label for the rule
            $table->string('label_en');               // English label
            $table->integer('followup_days');          // days after completion to schedule follow-up
            $table->boolean('auto_create_booking')->default(true);   // auto-create a booking
            $table->boolean('sms_patient')->default(true);           // send SMS to patient
            $table->boolean('notify_doctor')->default(true);         // in-app notify doctor
            $table->boolean('notify_secretary')->default(true);      // in-app notify secretary
            $table->integer('sms_days_before')->default(1);          // SMS reminder X days before follow-up
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('treatment_type');
        });

        // Track scheduled follow-ups
        Schema::create('dental_scheduled_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dental_treatment_id')->constrained('dental_treatments')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('followup_rule_id')->nullable()->constrained('dental_followup_rules')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->date('scheduled_date');                          // the follow-up date
            $table->string('status')->default('pending');            // pending, booking_created, sms_sent, completed, cancelled
            $table->timestamp('booking_created_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();       // day-before SMS
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'scheduled_date']);
            $table->index(['status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_scheduled_followups');
        Schema::dropIfExists('dental_followup_rules');
    }
};

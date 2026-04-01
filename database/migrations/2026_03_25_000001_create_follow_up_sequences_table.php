<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->string('trigger_event');
            // e.g.: lead_created, status_changed_to_contacted, status_changed_to_qualified,
            // appointment_booked, consultation_done, booking_created, contact_form_submitted
            $table->json('trigger_conditions')->nullable();
            // e.g.: {"lead_source_id": 1, "priority": 1}
            $table->boolean('is_active')->default(true);
            $table->boolean('stop_on_reply')->default(true);
            $table->boolean('stop_on_conversion')->default(true);
            $table->unsignedInteger('max_enrollments')->nullable();
            // null = unlimited
            $table->unsignedInteger('enrollment_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('trigger_event');
            $table->index('is_active');
        });

        Schema::create('follow_up_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('follow_up_sequences')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order')->default(1);
            $table->unsignedInteger('delay_minutes')->default(60);
            // delay from previous step (or from enrollment for step 1)
            $table->string('action_type');
            // create_follow_up, send_whatsapp, send_email, send_sms, notify_staff, change_status, add_score
            $table->foreignId('template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->string('follow_up_type')->nullable();
            // for create_follow_up: call, whatsapp, email, sms, meeting
            $table->string('target_status')->nullable();
            // for change_status action
            $table->integer('score_points')->nullable();
            // for add_score action
            $table->string('message_en', 1000)->nullable();
            // custom message if no template
            $table->string('message_ar', 1000)->nullable();
            $table->string('notification_message', 500)->nullable();
            // for notify_staff action
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sequence_id', 'step_order']);
        });

        Schema::create('lead_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained('follow_up_sequences')->cascadeOnDelete();
            $table->unsignedSmallInteger('current_step_index')->default(0);
            // 0 = not started yet, 1 = first step done, etc.
            $table->string('status')->default('active');
            // active, paused, completed, cancelled, stopped_reply, stopped_conversion
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('next_step_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'sequence_id']);
            $table->index(['status', 'next_step_at']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sequence_enrollments');
        Schema::dropIfExists('follow_up_sequence_steps');
        Schema::dropIfExists('follow_up_sequences');
    }
};

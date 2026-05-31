<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drip sequences — automated multi-step journeys (welcome series, post-op care).
 * A sequence has ordered steps (each with a delay); recipients are enrolled and
 * a worker advances them step by step. Sends go through the hub as the marketing
 * sequence.message event, so consent / quiet-hours / frequency-cap all apply.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_sequences')) {
            Schema::create('notification_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('trigger_event')->nullable(); // auto-enrol when this event fires
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['trigger_event', 'is_active']);
            });
        }

        if (! Schema::hasTable('notification_sequence_steps')) {
            Schema::create('notification_sequence_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sequence_id')->constrained('notification_sequences')->cascadeOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->unsignedInteger('delay_minutes')->default(0); // wait before THIS step (from prior/enrol)
                $table->string('channel', 20)->nullable();            // null = route normally
                $table->string('subject')->nullable();
                $table->text('body_ar');
                $table->text('body_en')->nullable();
                $table->timestamps();
                $table->index(['sequence_id', 'position']);
            });
        }

        if (! Schema::hasTable('notification_sequence_enrollments')) {
            Schema::create('notification_sequence_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sequence_id')->constrained('notification_sequences')->cascadeOnDelete();
                $table->nullableMorphs('recipient', 'seqenr_recipient_idx'); // short name (64-char limit)
                $table->unsignedInteger('current_step')->default(0);
                $table->string('status', 20)->default('active'); // active|completed|cancelled
                $table->timestamp('next_run_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'next_run_at']);
                $table->index(['sequence_id', 'recipient_type', 'recipient_id'], 'seqenr_seq_recipient_idx');
            });
        }

        if (Schema::hasTable('notification_events')) {
            $now = now();
            DB::table('notification_events')->updateOrInsert(
                ['key' => 'sequence.message'],
                ['label_ar' => 'رسالة سلسلة', 'label_en' => 'Sequence Message', 'category' => 'marketing',
                    'default_channels' => json_encode(['whatsapp', 'sms', 'email']), 'is_active' => 1,
                    'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_sequence_enrollments');
        Schema::dropIfExists('notification_sequence_steps');
        Schema::dropIfExists('notification_sequences');
        if (Schema::hasTable('notification_events')) {
            DB::table('notification_events')->where('key', 'sequence.message')->delete();
        }
    }
};

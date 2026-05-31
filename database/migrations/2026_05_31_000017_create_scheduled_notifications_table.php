<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Holds notifications deferred to a future time — currently quiet-hours holds
 * (sent when the window opens) and the foundation for smart send-time. A worker
 * command processes due rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheduled_notifications')) {
            return;
        }

        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->nullableMorphs('recipient');
            $table->json('data')->nullable();
            $table->json('channels')->nullable();      // forced channels, if any
            $table->string('reason', 30)->default('quiet_hours');
            $table->timestamp('send_after');
            $table->string('status', 20)->default('pending'); // pending|processed|cancelled
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'send_after']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};

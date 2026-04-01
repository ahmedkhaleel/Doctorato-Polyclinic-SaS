<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();

            $table->enum('type', [
                'note', 'call', 'whatsapp', 'email', 'sms',
                'meeting', 'status_change', 'assignment',
                'follow_up_scheduled', 'follow_up_completed',
                'booking_created', 'visit_completed', 'payment_received',
                'system',  // auto-generated activities
            ]);

            $table->string('subject')->nullable();             // brief summary
            $table->text('description')->nullable();           // detailed content
            $table->json('metadata')->nullable();              // flexible extra data (old_status, new_status, etc.)

            // Communication details
            $table->enum('direction', ['inbound', 'outbound'])->nullable();
            $table->unsignedInteger('duration_seconds')->nullable(); // call/meeting duration
            $table->enum('outcome', ['successful', 'no_answer', 'busy', 'voicemail', 'callback_requested', 'not_interested'])->nullable();

            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};

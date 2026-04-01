<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();

            $table->enum('type', ['call', 'whatsapp', 'email', 'sms', 'meeting', 'other'])->default('call');
            $table->dateTime('scheduled_at');
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('result')->nullable();
            $table->enum('status', ['pending', 'completed', 'missed', 'cancelled', 'rescheduled'])->default('pending');
            $table->unsignedTinyInteger('reminder_sent')->default(0); // 0=not sent, 1=sent

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assigned_to', 'status', 'scheduled_at']);
            $table->index(['lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_follow_ups');
    }
};

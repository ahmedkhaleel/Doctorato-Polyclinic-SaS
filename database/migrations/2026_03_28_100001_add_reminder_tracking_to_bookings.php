<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('reminder_sms_sent_at')->nullable()->after('admin_notes');
            $table->timestamp('sameday_sms_sent_at')->nullable()->after('reminder_sms_sent_at');
        });

        // Track dental recall reminders per patient
        Schema::create('patient_recall_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('module', 20)->default('dental'); // dental, derma
            $table->string('type', 30)->default('checkup'); // checkup, cleaning, follow_up
            $table->date('last_visit_date')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('sms_status', 20)->nullable(); // sent, failed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'module', 'type']);
            $table->index('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['reminder_sms_sent_at', 'sameday_sms_sent_at']);
        });

        Schema::dropIfExists('patient_recall_reminders');
    }
};

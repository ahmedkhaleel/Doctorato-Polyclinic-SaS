<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('receptionist_id')->constrained('bookings')->nullOnDelete();
            $table->foreignId('booking_appointment_id')->nullable()->after('booking_id')->constrained('booking_appointments')->nullOnDelete();
            $table->time('scheduled_time')->nullable()->after('visit_date');

            $table->index('booking_id');
            $table->index('booking_appointment_id');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['booking_appointment_id']);
            $table->dropIndex(['booking_id']);
            $table->dropIndex(['booking_appointment_id']);
            $table->dropColumn(['booking_id', 'booking_appointment_id', 'scheduled_time']);
        });
    }
};

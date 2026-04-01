<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_retouch flag to booking_appointments
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->boolean('is_retouch')->default(false)->after('status');
        });

        // Add 'follow_up' to visits.visit_type enum
        DB::statement("ALTER TABLE visits MODIFY COLUMN visit_type ENUM('consultation', 'session', 'follow_up') NOT NULL DEFAULT 'session'");
    }

    public function down(): void
    {
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->dropColumn('is_retouch');
        });

        DB::statement("ALTER TABLE visits MODIFY COLUMN visit_type ENUM('consultation', 'session') NOT NULL DEFAULT 'session'");
    }
};

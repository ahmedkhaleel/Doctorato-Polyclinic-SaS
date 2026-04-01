<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decouple package bundle bookings from the regular bookings system.
     *
     * Previously, every PackageBundleBooking created a "shadow" Booking record
     * just to satisfy Visit.booking_id constraint. This migration removes that
     * coupling so the two booking systems are fully independent.
     */
    public function up(): void
    {
        // 1. Null out booking_id on visits that are linked to package bundle bookings
        //    (they already have package_bundle_booking_id set)
        DB::statement('
            UPDATE visits
            SET booking_id = NULL
            WHERE package_bundle_booking_id IS NOT NULL
              AND booking_id IS NOT NULL
        ');

        // 2. Null out booking_id on invoices that are linked to package bundle bookings
        //    (they already have package_bundle_booking_id set)
        DB::statement('
            UPDATE invoices
            SET booking_id = NULL
            WHERE package_bundle_booking_id IS NOT NULL
              AND booking_id IS NOT NULL
        ');

        // 3. Drop the booking_id FK and column from package_bundle_bookings
        Schema::table('package_bundle_bookings', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });

        // 4. Delete orphaned "shadow" Booking records that were created solely
        //    as parents for package bundle bookings
        DB::statement("
            DELETE FROM bookings WHERE booking_type = 'package_bundle'
        ");

        // 5. Add proper FK constraints for package bundle fields on visits table
        Schema::table('visits', function (Blueprint $table) {
            $table->foreign('package_bundle_booking_id')
                ->references('id')
                ->on('package_bundle_bookings')
                ->nullOnDelete();

            $table->foreign('package_bundle_booking_service_id')
                ->references('id')
                ->on('package_bundle_booking_services')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove FK constraints from visits
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['package_bundle_booking_id']);
            $table->dropForeign(['package_bundle_booking_service_id']);
        });

        // Re-add booking_id to package_bundle_bookings
        Schema::table('package_bundle_bookings', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
        });
    }
};

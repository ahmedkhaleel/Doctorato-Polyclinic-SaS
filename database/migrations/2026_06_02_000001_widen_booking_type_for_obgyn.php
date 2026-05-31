<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bookings.booking_type was a DB ENUM that did not include the OB/GYN values
 * (obgyn_consultation, obgyn_service) — so converting an OB/GYN lead or booking
 * failed with "Data truncated for column 'booking_type'" (HTTP 500).
 *
 * OB/GYN is a medical module (MEDICAL_MODULES) and must be bookable. Convert the
 * column to a plain VARCHAR; the allowed values are enforced in application
 * validation (BookingRequest / controllers), which is more flexible than a DB
 * enum and avoids a migration every time a module is added. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'booking_type')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_type', 40)->default('service')->change();
        });
    }

    public function down(): void
    {
        // Not reverted: re-imposing the old enum would reject any obgyn_* rows.
    }
};

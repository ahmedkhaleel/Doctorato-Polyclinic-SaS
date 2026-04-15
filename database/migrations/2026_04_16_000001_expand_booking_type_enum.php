<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_type ENUM(
            'dermatology_consultation',
            'cosmetic_consultation',
            'dental_consultation',
            'pediatric_consultation',
            'service',
            'dental_service',
            'pediatric_service',
            'package_bundle'
        ) NOT NULL DEFAULT 'service'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_type ENUM(
            'dermatology_consultation',
            'cosmetic_consultation',
            'dental_consultation',
            'service',
            'package_bundle'
        ) NOT NULL DEFAULT 'service'");
    }
};

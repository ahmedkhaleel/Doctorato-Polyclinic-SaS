<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill module from linked visit
        DB::statement("
            UPDATE invoices
            JOIN visits ON invoices.visit_id = visits.id
            SET invoices.module = visits.module
            WHERE invoices.module IS NULL
              AND visits.module IS NOT NULL
        ");

        // Backfill module from linked booking
        DB::statement("
            UPDATE invoices
            JOIN bookings ON invoices.booking_id = bookings.id
            SET invoices.module = bookings.module
            WHERE invoices.module IS NULL
              AND bookings.module IS NOT NULL
        ");
    }

    public function down(): void
    {
        // No rollback needed
    }
};

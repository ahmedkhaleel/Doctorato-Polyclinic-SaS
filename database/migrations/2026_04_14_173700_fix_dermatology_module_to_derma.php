<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize all "dermatology" module values to "derma" across all tables.
     * The canonical module slugs are: derma, dental, pediatric.
     */
    public function up(): void
    {
        // Fix bookings table
        DB::table('bookings')
            ->where('module', 'dermatology')
            ->update(['module' => 'derma']);

        // Fix visits table
        DB::table('visits')
            ->where('module', 'dermatology')
            ->update(['module' => 'derma']);

        // Fix leads table (if any)
        DB::table('leads')
            ->where('module', 'dermatology')
            ->update(['module' => 'derma']);

        // Fix invoices table (if any)
        if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'module')) {
            DB::table('invoices')
                ->where('module', 'dermatology')
                ->update(['module' => 'derma']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback - data was incorrect before
    }
};

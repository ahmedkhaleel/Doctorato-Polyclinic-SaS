<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch B1 — bookings domain. Adds nullable branch_id + backfills every
 * existing row to the Main Branch (default_id), then a composite index. Additive
 * and data-safe: nullable + backfill = zero behaviour change.
 */
return new class extends Migration
{
    private array $tables = [
        'bookings',
        'booking_appointments',
        'booking_services',
        'booking_consents',
        'package_bundle_bookings',
        'package_bundle_booking_appointments',
        'package_bundle_booking_services',
        'online_consultations',
    ];

    public function up(): void
    {
        $default = (int) config('branches.default_id', 1);

        foreach ($this->tables as $t) {
            if (! Schema::hasTable($t) || Schema::hasColumn($t, 'branch_id')) {
                continue;
            }
            Schema::table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->index(['branch_id', 'created_at']);
            });
            DB::table($t)->whereNull('branch_id')->update(['branch_id' => $default]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'branch_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropIndex(['branch_id', 'created_at']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};

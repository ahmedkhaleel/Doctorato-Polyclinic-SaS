<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('package_bundle_booking_id')->nullable()->after('service_package_id');

            $table->foreign('package_bundle_booking_id', 'invoices_pbb_id_fk')
                ->references('id')->on('package_bundle_bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign('invoices_pbb_id_fk');
            $table->dropColumn('package_bundle_booking_id');
        });
    }
};

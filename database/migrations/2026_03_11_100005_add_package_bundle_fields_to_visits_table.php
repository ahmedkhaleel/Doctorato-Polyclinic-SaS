<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->unsignedBigInteger('package_bundle_booking_id')->nullable()->after('booking_appointment_id');
            $table->unsignedBigInteger('package_bundle_booking_service_id')->nullable()->after('package_bundle_booking_id');

            $table->foreign('package_bundle_booking_id', 'visits_pbb_id_fk')
                ->references('id')->on('package_bundle_bookings')->nullOnDelete();

            $table->foreign('package_bundle_booking_service_id', 'visits_pbbs_id_fk')
                ->references('id')->on('package_bundle_booking_services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign('visits_pbbs_id_fk');
            $table->dropForeign('visits_pbb_id_fk');
            $table->dropColumn(['package_bundle_booking_service_id', 'package_bundle_booking_id']);
        });
    }
};

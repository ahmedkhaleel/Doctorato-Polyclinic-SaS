<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_usage', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('invoice_id')
                ->constrained('bookings')->nullOnDelete();
            $table->foreignId('package_bundle_booking_id')->nullable()->after('booking_id')
                ->constrained('package_bundle_bookings')->nullOnDelete();
            $table->string('ip_address', 45)->nullable()->after('discount_amount');
            $table->text('user_agent')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('discount_usage', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['package_bundle_booking_id']);
            $table->dropColumn(['booking_id', 'package_bundle_booking_id', 'ip_address', 'user_agent']);
        });
    }
};

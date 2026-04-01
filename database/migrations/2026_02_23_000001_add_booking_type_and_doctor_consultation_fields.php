<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A) Add booking_type to bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('booking_type', ['dermatology_consultation', 'cosmetic_consultation', 'service'])
                ->nullable()
                ->after('source');
        });

        // B) Add consultation_type to visits
        Schema::table('visits', function (Blueprint $table) {
            $table->enum('consultation_type', ['dermatology', 'cosmetic'])
                ->nullable()
                ->after('visit_type');
        });

        // C) Add commission & fee fields to doctors
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('dermatology_fee', 10, 2)->nullable()->after('consultation_fee');
            $table->decimal('cosmetic_fee', 10, 2)->nullable()->after('dermatology_fee');
            $table->decimal('dermatology_commission', 5, 2)->nullable()->after('cosmetic_fee');
            $table->decimal('cosmetic_commission', 5, 2)->nullable()->after('dermatology_commission');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_type');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('consultation_type');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['dermatology_fee', 'cosmetic_fee', 'dermatology_commission', 'cosmetic_commission']);
        });
    }
};

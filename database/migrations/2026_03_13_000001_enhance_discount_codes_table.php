<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            // Package bundle targeting
            $table->json('applicable_packages')->nullable()->after('applicable_services');

            // Smart conditions
            $table->decimal('min_order_amount', 10, 2)->default(0)->after('applicable_packages');
            $table->decimal('max_discount_amount', 10, 2)->nullable()->after('min_order_amount');
            $table->integer('per_patient_limit')->nullable()->after('max_discount_amount');
            $table->boolean('first_booking_only')->default(false)->after('per_patient_limit');

            // Website popup settings
            $table->boolean('show_on_website')->default(false)->after('first_booking_only');
            $table->string('popup_title_en')->nullable()->after('show_on_website');
            $table->string('popup_title_ar')->nullable()->after('popup_title_en');
            $table->text('popup_description_en')->nullable()->after('popup_title_ar');
            $table->text('popup_description_ar')->nullable()->after('popup_description_en');
            $table->string('popup_image')->nullable()->after('popup_description_ar');
        });
    }

    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn([
                'applicable_packages',
                'min_order_amount', 'max_discount_amount',
                'per_patient_limit', 'first_booking_only',
                'show_on_website',
                'popup_title_en', 'popup_title_ar',
                'popup_description_en', 'popup_description_ar',
                'popup_image',
            ]);
        });
    }
};

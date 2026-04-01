<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_bundle_booking_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_bundle_booking_id');
            $table->unsignedBigInteger('package_bundle_service_id');
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->integer('sessions_count')->default(1);
            $table->integer('completed_sessions')->default(0);
            $table->decimal('bundle_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('package_bundle_booking_id', 'pbbs_booking_id_fk')
                ->references('id')->on('package_bundle_bookings')->cascadeOnDelete();

            $table->foreign('package_bundle_service_id', 'pbbs_bundle_service_id_fk')
                ->references('id')->on('package_bundle_services')->restrictOnDelete();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_bundle_booking_services');
    }
};

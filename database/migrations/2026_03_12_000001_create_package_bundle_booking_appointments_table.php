<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add appointment scheduling layer for package bundle bookings.
     *
     * Mirrors the booking_appointments table structure so that bundle
     * bookings follow the same flow as service bookings:
     * booking -> services -> appointments -> visits (on check-in).
     */
    public function up(): void
    {
        Schema::create('package_bundle_booking_appointments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('package_bundle_booking_id');
            $table->foreign('package_bundle_booking_id', 'pbba_booking_id_fk')
                ->references('id')->on('package_bundle_bookings')->cascadeOnDelete();

            $table->unsignedBigInteger('package_bundle_booking_service_id');
            $table->foreign('package_bundle_booking_service_id', 'pbba_service_id_fk')
                ->references('id')->on('package_bundle_booking_services')->cascadeOnDelete();

            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();

            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('session_number')->default(1);

            $table->string('status')->default('scheduled');
            // Valid statuses: scheduled, confirmed, checked_in, in_progress, completed, cancelled, no_show

            $table->boolean('is_retouch')->default(false);
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'appointment_date', 'start_time'], 'pbba_doctor_date_time_idx');
            $table->index(['package_bundle_booking_id', 'status'], 'pbba_booking_status_idx');
            $table->index(['appointment_date', 'status'], 'pbba_date_status_idx');
        });

        // Add FK on visits table to link back to bundle appointments
        Schema::table('visits', function (Blueprint $table) {
            $table->unsignedBigInteger('package_bundle_booking_appointment_id')
                ->nullable()
                ->after('package_bundle_booking_service_id');

            $table->foreign('package_bundle_booking_appointment_id', 'visits_pbba_id_fk')
                ->references('id')
                ->on('package_bundle_booking_appointments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign('visits_pbba_id_fk');
            $table->dropColumn('package_bundle_booking_appointment_id');
        });

        Schema::dropIfExists('package_bundle_booking_appointments');
    }
};

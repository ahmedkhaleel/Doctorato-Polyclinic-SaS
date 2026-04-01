<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add module column to doctors
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('module', 50)->default('derma')->after('status')->index();
        });

        // Add module column to services
        Schema::table('services', function (Blueprint $table) {
            $table->string('module', 50)->default('derma')->after('status')->index();
        });

        // Add module column to service_categories
        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('module', 50)->default('derma')->after('id')->index();
        });

        // Add module column to bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('module', 50)->default('derma')->after('source')->index();
        });

        // Add module column to visits
        Schema::table('visits', function (Blueprint $table) {
            $table->string('module', 50)->default('derma')->after('visit_type')->index();
        });

        // Add module column to leads
        Schema::table('leads', function (Blueprint $table) {
            $table->string('module', 50)->default('derma')->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('module');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('module');
        });
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn('module');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('module');
        });
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('module');
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('module');
        });
    }
};

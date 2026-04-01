<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add lead_id to patients table (link converted leads to patients)
        if (! Schema::hasColumn('patients', 'lead_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->foreignId('lead_id')->nullable()->after('id')->constrained('leads')->nullOnDelete();
                $table->index('lead_id');
            });
        }

        // Add lead_id to bookings table (track which lead generated this booking)
        if (! Schema::hasColumn('bookings', 'lead_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('lead_id')->nullable()->after('id')->constrained('leads')->nullOnDelete();
                $table->index('lead_id');
            });
        }

        // Add lead_id to contact_messages table (auto-link website contact forms to leads)
        if (! Schema::hasColumn('contact_messages', 'lead_id')) {
            Schema::table('contact_messages', function (Blueprint $table) {
                $table->foreignId('lead_id')->nullable()->after('id')->constrained('leads')->nullOnDelete();
                $table->index('lead_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('patients', 'lead_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropForeign(['lead_id']);
                $table->dropColumn('lead_id');
            });
        }

        if (Schema::hasColumn('bookings', 'lead_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropForeign(['lead_id']);
                $table->dropColumn('lead_id');
            });
        }

        if (Schema::hasColumn('contact_messages', 'lead_id')) {
            Schema::table('contact_messages', function (Blueprint $table) {
                $table->dropForeign(['lead_id']);
                $table->dropColumn('lead_id');
            });
        }
    }
};

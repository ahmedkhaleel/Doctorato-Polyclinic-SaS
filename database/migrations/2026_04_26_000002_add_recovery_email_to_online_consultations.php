<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when we last reminded a patient about their abandoned-payment
 * online consultation. Lets us send the recovery email *once* per
 * consultation and not pester the same patient hourly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('online_consultations')) return;

        Schema::table('online_consultations', function (Blueprint $table) {
            if (! Schema::hasColumn('online_consultations', 'recovery_email_sent_at')) {
                $table->timestamp('recovery_email_sent_at')->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('online_consultations', function (Blueprint $table) {
            if (Schema::hasColumn('online_consultations', 'recovery_email_sent_at')) {
                $table->dropColumn('recovery_email_sent_at');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-patient notification preferences. Defaults to "yes for everything"
 * to preserve existing behavior — admin/secretary can still email/SMS
 * patients on real medical events. Patients can opt out from their
 * profile page.
 *
 * Three categories × two channels:
 *   - bookings   (created / confirmed / cancelled)
 *   - reminders  (24h-before / same-day SMS)
 *   - marketing  (promo codes, discounts, surveys)
 *
 * preferred_language drives the locale of any notification template.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients')) return;

        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'preferred_language')) {
                $table->string('preferred_language', 5)->default('ar')->after('email');
            }

            // Email opt-ins
            if (! Schema::hasColumn('patients', 'notify_email_bookings')) {
                $table->boolean('notify_email_bookings')->default(true);
            }
            if (! Schema::hasColumn('patients', 'notify_email_reminders')) {
                $table->boolean('notify_email_reminders')->default(true);
            }
            if (! Schema::hasColumn('patients', 'notify_email_marketing')) {
                $table->boolean('notify_email_marketing')->default(true);
            }

            // SMS opt-ins
            if (! Schema::hasColumn('patients', 'notify_sms_bookings')) {
                $table->boolean('notify_sms_bookings')->default(true);
            }
            if (! Schema::hasColumn('patients', 'notify_sms_reminders')) {
                $table->boolean('notify_sms_reminders')->default(true);
            }
            if (! Schema::hasColumn('patients', 'notify_sms_marketing')) {
                $table->boolean('notify_sms_marketing')->default(false);  // marketing SMS is opt-IN
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $cols = [
                'preferred_language',
                'notify_email_bookings', 'notify_email_reminders', 'notify_email_marketing',
                'notify_sms_bookings', 'notify_sms_reminders', 'notify_sms_marketing',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('patients', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};

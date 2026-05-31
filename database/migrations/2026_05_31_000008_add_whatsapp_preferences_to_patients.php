<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp notification opt-ins for patients, mirroring the email/SMS prefs.
 *   - bookings / reminders default ON (transactional+reminder value)
 *   - marketing defaults OFF (opt-IN, same policy as marketing SMS)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'notify_whatsapp_bookings')) {
                $table->boolean('notify_whatsapp_bookings')->default(true);
            }
            if (! Schema::hasColumn('patients', 'notify_whatsapp_reminders')) {
                $table->boolean('notify_whatsapp_reminders')->default(true);
            }
            if (! Schema::hasColumn('patients', 'notify_whatsapp_marketing')) {
                $table->boolean('notify_whatsapp_marketing')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            foreach (['notify_whatsapp_bookings', 'notify_whatsapp_reminders', 'notify_whatsapp_marketing'] as $c) {
                if (Schema::hasColumn('patients', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};

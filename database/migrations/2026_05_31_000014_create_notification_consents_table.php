<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Time-stamped consent log — an audit trail of every opt-in/opt-out change
 * (channel × category), with the source (patient_portal / admin / stop_keyword
 * / unsubscribe_link). Append-only; the patient row holds the current state.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_consents')) {
            return;
        }

        Schema::create('notification_consents', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('recipient');
            $table->string('channel', 20);     // email | sms | whatsapp
            $table->string('category', 20);    // bookings | reminders | marketing
            $table->boolean('opted_in');
            $table->string('source', 40)->default('admin');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['recipient_type', 'recipient_id', 'channel', 'category'], 'nc_recipient_chan_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_consents');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add provider_ref to notification_logs so provider message IDs (WhatsApp
 * wamid, SMS Misr SMSID, etc.) can be matched back from delivery webhooks.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_logs') || Schema::hasColumn('notification_logs', 'provider_ref')) {
            return;
        }

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('provider_ref', 191)->nullable()->after('provider');
            $table->index('provider_ref');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_logs') && Schema::hasColumn('notification_logs', 'provider_ref')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $table->dropIndex(['provider_ref']);
                $table->dropColumn('provider_ref');
            });
        }
    }
};

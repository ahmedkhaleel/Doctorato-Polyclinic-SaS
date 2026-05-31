<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A/B testing for campaigns: an optional variant B + attribution columns on the
 * log so per-variant delivery/read can be compared portably (no JSON queries).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_campaigns')) {
            Schema::table('notification_campaigns', function (Blueprint $table) {
                if (! Schema::hasColumn('notification_campaigns', 'ab_enabled')) {
                    $table->boolean('ab_enabled')->default(false);
                    $table->string('subject_b')->nullable();
                    $table->text('body_ar_b')->nullable();
                    $table->text('body_en_b')->nullable();
                }
            });
        }

        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('notification_logs', 'campaign_id')) {
                    $table->unsignedBigInteger('campaign_id')->nullable()->after('event_key');
                    $table->string('ab_variant', 1)->nullable()->after('campaign_id');
                    $table->index(['campaign_id', 'ab_variant']);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_logs') && Schema::hasColumn('notification_logs', 'campaign_id')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $table->dropIndex(['campaign_id', 'ab_variant']);
                $table->dropColumn(['campaign_id', 'ab_variant']);
            });
        }
        if (Schema::hasTable('notification_campaigns') && Schema::hasColumn('notification_campaigns', 'ab_enabled')) {
            Schema::table('notification_campaigns', function (Blueprint $table) {
                $table->dropColumn(['ab_enabled', 'subject_b', 'body_ar_b', 'body_en_b']);
            });
        }
    }
};

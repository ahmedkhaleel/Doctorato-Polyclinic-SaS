<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing campaigns: a message + audience rules sent to a patient segment via
 * one channel, now or scheduled. Sends go through Notifier as the marketing
 * `campaign.message` event, so consent / quiet-hours / frequency-cap all apply.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_campaigns')) {
            Schema::create('notification_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('channel', 20)->default('whatsapp');
                $table->string('subject')->nullable();
                $table->text('body_ar');
                $table->text('body_en')->nullable();
                $table->json('rules')->nullable();                 // audience filter
                $table->string('status', 20)->default('draft');    // draft|scheduled|sending|sent
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->unsignedInteger('audience_count')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['status', 'scheduled_at']);
            });
        }

        // Marketing event used by every campaign send (template-free; body is supplied).
        if (Schema::hasTable('notification_events')) {
            $now = now();
            DB::table('notification_events')->updateOrInsert(
                ['key' => 'campaign.message'],
                ['label_ar' => 'رسالة حملة', 'label_en' => 'Campaign Message', 'category' => 'marketing',
                    'default_channels' => json_encode(['whatsapp', 'sms', 'email']), 'is_active' => 1,
                    'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_campaigns');
        if (Schema::hasTable('notification_events')) {
            DB::table('notification_events')->where('key', 'campaign.message')->delete();
        }
    }
};

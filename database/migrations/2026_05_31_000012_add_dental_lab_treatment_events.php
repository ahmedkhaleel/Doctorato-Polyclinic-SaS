<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two new transactional events so the dental lab-ready + treatment-plan-approved
 * notifications can route through the hub. Idempotent (updateOrInsert).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_events')) {
            return;
        }

        $now = now();

        $events = [
            ['dental.lab_ready', 'طلب المعمل جاهز', 'Dental Lab Order Ready', ['whatsapp', 'sms', 'in_app']],
            ['dental.treatment_plan_approved', 'الموافقة على خطة العلاج', 'Treatment Plan Approved', ['whatsapp', 'sms', 'in_app']],
            // Ad-hoc staff messages: no template (the typed body is the content),
            // transactional so consent is bypassed; channels chosen explicitly.
            ['manual.message', 'رسالة يدوية', 'Manual Message', ['in_app']],
        ];

        foreach ($events as [$key, $ar, $en, $channels]) {
            DB::table('notification_events')->updateOrInsert(
                ['key' => $key],
                ['label_ar' => $ar, 'label_en' => $en, 'category' => 'transactional',
                    'default_channels' => json_encode($channels), 'is_active' => 1,
                    'updated_at' => $now, 'created_at' => $now]
            );
            foreach ($channels as $i => $channel) {
                DB::table('notification_channel_routes')->updateOrInsert(
                    ['event_key' => $key, 'channel' => $channel],
                    ['enabled' => 1, 'priority' => $i, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_events')) {
            return;
        }
        foreach (['dental.lab_ready', 'dental.treatment_plan_approved', 'manual.message'] as $key) {
            DB::table('notification_channel_routes')->where('event_key', $key)->delete();
            DB::table('notification_events')->where('key', $key)->delete();
        }
    }
};

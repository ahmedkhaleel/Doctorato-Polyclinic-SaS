<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Admin alert when notification spend crosses the monthly cost cap (in_app). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_events')) {
            return;
        }
        $now = now();
        DB::table('notification_events')->updateOrInsert(
            ['key' => 'staff.cost_alert'],
            ['label_ar' => 'تنبيه تكلفة الإشعارات', 'label_en' => 'Notification Cost Alert', 'category' => 'transactional',
                'default_channels' => json_encode(['in_app']), 'is_active' => 1, 'updated_at' => $now, 'created_at' => $now]
        );
        DB::table('notification_channel_routes')->updateOrInsert(
            ['event_key' => 'staff.cost_alert', 'channel' => 'in_app'],
            ['enabled' => 1, 'priority' => 0, 'updated_at' => $now, 'created_at' => $now]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_events')) {
            DB::table('notification_channel_routes')->where('event_key', 'staff.cost_alert')->delete();
            DB::table('notification_events')->where('key', 'staff.cost_alert')->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Operational staff events (recipient = User), in_app only. Idempotent.
 * Staff alerts are internal and transactional (no consent gate).
 */
return new class extends Migration
{
    private array $events = [
        ['staff.booking_new', 'حجز جديد', 'New Booking'],
        ['staff.patient_reply', 'رد من مريض', 'Patient Reply'],
        ['staff.low_stock', 'مخزون منخفض', 'Low Stock'],
        ['staff.lab_critical', 'نتيجة مخبرية حرجة', 'Critical Lab Result'],
        ['staff.leave_request', 'طلب إجازة', 'Leave Request'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('notification_events')) {
            return;
        }

        $now = now();
        foreach ($this->events as [$key, $ar, $en]) {
            DB::table('notification_events')->updateOrInsert(
                ['key' => $key],
                ['label_ar' => $ar, 'label_en' => $en, 'category' => 'transactional',
                    'default_channels' => json_encode(['in_app']), 'is_active' => 1,
                    'updated_at' => $now, 'created_at' => $now]
            );
            DB::table('notification_channel_routes')->updateOrInsert(
                ['event_key' => $key, 'channel' => 'in_app'],
                ['enabled' => 1, 'priority' => 0, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_events')) {
            return;
        }
        foreach ($this->events as [$key]) {
            DB::table('notification_channel_routes')->where('event_key', $key)->delete();
            DB::table('notification_events')->where('key', $key)->delete();
        }
    }
};

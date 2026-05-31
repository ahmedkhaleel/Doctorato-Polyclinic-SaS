<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the notification channels + the event catalogue + default routing.
 * Idempotent (updateOrInsert). Channels start disabled except in_app.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_channels')) {
            return;
        }

        $now = now();

        // ── Channels ───────────────────────────────────────
        foreach ([
            ['channel' => 'in_app', 'enabled' => 1, 'provider' => 'internal', 'from_name' => null],
            ['channel' => 'email', 'enabled' => 0, 'provider' => 'smtp', 'from_name' => 'Doctorato'],
            ['channel' => 'sms', 'enabled' => 0, 'provider' => 'smsmisr', 'from_name' => 'Doctorato'],
            ['channel' => 'whatsapp', 'enabled' => 0, 'provider' => 'cloud_api', 'from_name' => 'Doctorato'],
        ] as $c) {
            DB::table('notification_channels')->updateOrInsert(
                ['channel' => $c['channel']],
                array_merge($c, ['updated_at' => $now, 'created_at' => $now])
            );
        }

        // ── Event catalogue [key, ar, en, category, channels] ──
        $events = [
            ['booking.confirmed', 'تأكيد الحجز', 'Booking Confirmed', 'transactional', ['whatsapp', 'sms', 'in_app']],
            ['booking.rescheduled', 'تغيير موعد الحجز', 'Booking Rescheduled', 'transactional', ['whatsapp', 'sms', 'in_app']],
            ['booking.cancelled', 'إلغاء الحجز', 'Booking Cancelled', 'transactional', ['whatsapp', 'sms', 'in_app']],
            ['appointment.reminder.day_before', 'تذكير قبل الموعد بيوم', 'Reminder (Day Before)', 'reminder', ['whatsapp', 'sms']],
            ['appointment.reminder.same_day', 'تذكير يوم الموعد', 'Reminder (Same Day)', 'reminder', ['whatsapp', 'sms']],
            ['visit.completed', 'اكتمال الزيارة', 'Visit Completed', 'transactional', ['sms', 'in_app']],
            ['dental.followup', 'متابعة الأسنان', 'Dental Follow-up', 'reminder', ['whatsapp', 'sms']],
            ['derma.recall', 'استدعاء الجلدية', 'Derma Recall', 'reminder', ['whatsapp', 'sms']],
            ['pediatric.vaccination_due', 'تطعيم مستحق', 'Vaccination Due', 'reminder', ['whatsapp', 'sms']],
            ['pediatric.vaccination_overdue', 'تطعيم متأخر', 'Vaccination Overdue', 'reminder', ['whatsapp', 'sms']],
            ['obgyn.anc_reminder', 'تذكير متابعة الحمل', 'Antenatal Reminder', 'reminder', ['whatsapp', 'sms']],
            ['obgyn.edd_approaching', 'قرب موعد الولادة', 'EDD Approaching', 'reminder', ['whatsapp', 'sms']],
            ['obgyn.pap_recall', 'تجديد مسحة عنق الرحم', 'Pap Smear Recall', 'reminder', ['whatsapp', 'sms']],
            ['lead.welcome', 'ترحيب بالعميل', 'Lead Welcome', 'marketing', ['whatsapp', 'sms']],
            ['lead.follow_up_due', 'متابعة عميل محتمل', 'Lead Follow-up', 'marketing', ['whatsapp', 'sms']],
            ['lead.reactivation', 'إعادة تفعيل عميل', 'Lead Reactivation', 'marketing', ['whatsapp', 'sms']],
            ['invoice.overdue', 'فاتورة متأخرة', 'Invoice Overdue', 'transactional', ['sms', 'email', 'in_app']],
            ['payment.received', 'تأكيد الدفع', 'Payment Received', 'transactional', ['sms', 'in_app']],
            ['loyalty.earned', 'نقاط ولاء', 'Loyalty Earned', 'transactional', ['sms', 'in_app']],
            ['account.created', 'إنشاء حساب', 'Account Created', 'transactional', ['email', 'sms']],
            ['password.reset', 'إعادة تعيين كلمة المرور', 'Password Reset', 'transactional', ['email']],
        ];

        foreach ($events as [$key, $ar, $en, $cat, $channels]) {
            DB::table('notification_events')->updateOrInsert(
                ['key' => $key],
                ['label_ar' => $ar, 'label_en' => $en, 'category' => $cat, 'default_channels' => json_encode($channels), 'is_active' => 1, 'updated_at' => $now, 'created_at' => $now]
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
        foreach (['notification_channel_routes', 'notification_events', 'notification_channels'] as $t) {
            if (Schema::hasTable($t)) {
                DB::table($t)->delete();
            }
        }
    }
};

<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ─── Bookings ─────────────────────────────────────
            [
                'key'      => 'booking_confirmed',
                'category' => 'bookings',
                'body_ar'  => 'مرحباً {{patient_name}}، تم تأكيد حجزك في عيادة Doctorato يوم {{date}} الساعة {{time}}. للاستفسار: {{clinic_phone}}',
                'body_en'  => 'Hi {{patient_name}}, your booking at Doctorato is confirmed for {{date}} at {{time}}. Questions? {{clinic_phone}}',
                'description'  => 'Sent after secretary/admin confirms a booking.',
                'placeholders' => ['patient_name', 'date', 'time', 'clinic_phone'],
            ],
            [
                'key'      => 'booking_cancelled',
                'category' => 'bookings',
                'body_ar'  => 'تم إلغاء حجزك يوم {{date}}. يمكنكِ حجز موعد جديد في أي وقت من موقعنا. شكراً.',
                'body_en'  => 'Your booking on {{date}} has been cancelled. You can book another anytime via our website. Thanks.',
                'description'  => 'Sent when a booking is cancelled.',
                'placeholders' => ['patient_name', 'date'],
            ],

            // ─── Reminders ────────────────────────────────────
            [
                'key'      => 'reminder_day_before',
                'category' => 'reminders',
                'body_ar'  => 'تذكير: لديكِ موعد غداً ({{date}}) الساعة {{time}} في عيادة Doctorato.',
                'body_en'  => 'Reminder: You have an appointment tomorrow ({{date}}) at {{time}} at Doctorato Clinic.',
                'description'  => 'Sent 24 hours before the appointment.',
                'placeholders' => ['patient_name', 'date', 'time'],
            ],
            [
                'key'      => 'reminder_same_day',
                'category' => 'reminders',
                'body_ar'  => 'مرحباً {{patient_name}}، نتطلع لاستقبالكِ اليوم الساعة {{time}}. يرجى الحضور قبل الموعد بـ 10 دقائق.',
                'body_en'  => 'Hi {{patient_name}}, see you today at {{time}}. Please arrive 10 minutes early.',
                'description'  => 'Sent the morning of the appointment.',
                'placeholders' => ['patient_name', 'time'],
            ],
            [
                'key'      => 'recall_reminder',
                'category' => 'reminders',
                'body_ar'  => 'مرحباً {{patient_name}}، حان وقت زيارتك الدورية. احجزي الآن: {{clinic_phone}}',
                'body_en'  => 'Hi {{patient_name}}, time for your follow-up visit. Book now: {{clinic_phone}}',
                'description'  => 'Sent for periodic recall (every X months).',
                'placeholders' => ['patient_name', 'clinic_phone'],
            ],

            // ─── Marketing ────────────────────────────────────
            [
                'key'      => 'promo_code',
                'category' => 'marketing',
                'body_ar'  => 'عرض خاص: استخدمي الكوبون {{code}} للحصول على خصم {{discount}}%. {{clinic_phone}}',
                'body_en'  => 'Special offer: Use code {{code}} for {{discount}}% off. {{clinic_phone}}',
                'description'  => 'Promotional broadcast (opt-IN only).',
                'placeholders' => ['code', 'discount', 'clinic_phone'],
            ],
        ];

        foreach ($templates as $t) {
            SmsTemplate::updateOrCreate(['key' => $t['key']], $t);
        }

        $this->command->info('✓ Seeded ' . count($templates) . ' SMS templates.');
    }
}

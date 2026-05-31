<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade the auto-seeded booking/reminder templates to the richer bilingual
 * content the code used to build inline, now that those senders route through
 * the hub. Guarded: only rows still untouched by an admin (updated_at == the
 * created_at the P7 seed stamped) are changed — never clobbers an edit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        $defs = [
            'booking.confirmed' => [
                "مرحباً {{name}} 👋\nتم تأكيد حجزك في {{clinic_name}}\nرقم الحجز: {{booking_number}}\nالتاريخ: {{date}} {{time}}\nالقسم: {{module}}\nللاستفسار: {{clinic_phone}}\nشكراً لاختياركم 🌟",
                "Hello {{name}} 👋\nYour booking at {{clinic_name}} is confirmed.\nBooking: {{booking_number}}\nDate: {{date}} {{time}}\nDept: {{module}}\nInfo: {{clinic_phone}}",
            ],
            'appointment.reminder.day_before' => [
                "تذكير 📋\nمرحباً {{name}}\nلديك موعد غداً في {{clinic_name}}\nالوقت: {{time}}\nلإعادة الجدولة: {{clinic_phone}}\nنتطلع لرؤيتك! 😊",
                "Reminder 📋\nHello {{name}}\nYou have an appointment tomorrow at {{clinic_name}}\nTime: {{time}}\nReschedule: {{clinic_phone}}\nSee you! 😊",
            ],
            'appointment.reminder.same_day' => [
                "مرحباً {{name}}\nنذكركم بموعدكم اليوم في {{clinic_name}}\nالوقت: {{time}}\nللاستفسار: {{clinic_phone}}\nنتمنى لكم زيارة طيبة.",
                "Hello {{name}}\nReminder of your appointment today at {{clinic_name}}\nTime: {{time}}\nInfo: {{clinic_phone}}\nWe wish you a pleasant visit.",
            ],
        ];

        foreach ($defs as $eventKey => [$ar, $en]) {
            foreach (['sms', 'whatsapp'] as $channel) {
                DB::table('notification_templates')
                    ->where('event_key', $eventKey)
                    ->where('channel', $channel)
                    ->whereColumn('updated_at', 'created_at') // untouched by admin
                    ->update(['body_ar' => $ar, 'body_en' => $en, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Content-only upgrade; nothing to revert.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed sensible default message templates so the hub works out of the box.
 * Insert-only — never overwrites a template an admin has already edited.
 * Each event gets the same body on sms + whatsapp; a few also get email.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        $now = now();

        // [event_key, ar, en, channels[], subject?]
        $defs = [
            ['booking.confirmed',
                'مرحباً {{name}}، تم تأكيد حجزك يوم {{date}} الساعة {{time}}. {{clinic_name}}',
                'Hello {{name}}, your booking on {{date}} at {{time}} is confirmed. {{clinic_name}}',
                ['sms', 'whatsapp']],
            ['booking.rescheduled',
                'مرحباً {{name}}، تم تغيير موعد حجزك إلى {{date}} الساعة {{time}}. {{clinic_name}}',
                'Hello {{name}}, your booking was moved to {{date}} at {{time}}. {{clinic_name}}',
                ['sms', 'whatsapp']],
            ['booking.cancelled',
                'مرحباً {{name}}، تم إلغاء حجزك يوم {{date}}. للحجز من جديد تواصل معنا. {{clinic_name}}',
                'Hello {{name}}, your booking on {{date}} was cancelled. Contact us to rebook. {{clinic_name}}',
                ['sms', 'whatsapp']],
            ['appointment.reminder.day_before',
                'تذكير: لديك موعد غداً {{date}} الساعة {{time}} في {{clinic_name}}.',
                'Reminder: you have an appointment tomorrow {{date}} at {{time}} at {{clinic_name}}.',
                ['sms', 'whatsapp']],
            ['appointment.reminder.same_day',
                'تذكير: موعدك اليوم الساعة {{time}} في {{clinic_name}}. ننتظرك!',
                'Reminder: your appointment is today at {{time}} at {{clinic_name}}. See you!',
                ['sms', 'whatsapp']],
            ['payment.received',
                'تم استلام دفعتك بمبلغ {{amount}}. شكراً لك. {{clinic_name}}',
                'We received your payment of {{amount}}. Thank you. {{clinic_name}}',
                ['sms', 'whatsapp']],
            ['invoice.overdue',
                'تذكير: لديك فاتورة مستحقة بمبلغ {{amount}}. يرجى السداد. {{clinic_name}}',
                'Reminder: you have an outstanding invoice of {{amount}}. Please settle it. {{clinic_name}}',
                ['sms', 'whatsapp', 'email'], 'Outstanding invoice — {{clinic_name}}'],
            ['loyalty.earned',
                'حصلت على {{points}} نقطة ولاء! رصيدك الآن {{balance}}. {{clinic_name}}',
                'You earned {{points}} loyalty points! Your balance is now {{balance}}. {{clinic_name}}',
                ['sms', 'whatsapp']],
            ['lead.welcome',
                'مرحباً {{name}}، شكراً لتواصلك مع {{clinic_name}}. سنعاود الاتصال بك قريباً.',
                'Hello {{name}}, thank you for contacting {{clinic_name}}. We will reach out soon.',
                ['sms', 'whatsapp']],
            ['dental.followup',
                'مرحباً {{name}}، حان وقت متابعة الأسنان. احجز موعدك مع {{clinic_name}}.',
                'Hello {{name}}, it is time for your dental follow-up. Book with {{clinic_name}}.',
                ['sms', 'whatsapp']],
            ['pediatric.vaccination_due',
                'تذكير: تطعيم {{name}} مستحق بتاريخ {{date}}. {{clinic_name}}',
                'Reminder: {{name}}\'s vaccination is due on {{date}}. {{clinic_name}}',
                ['sms', 'whatsapp']],
            ['obgyn.anc_reminder',
                'تذكير متابعة الحمل: موعدك {{date}} الساعة {{time}}. {{clinic_name}}',
                'Antenatal reminder: your visit is on {{date}} at {{time}}. {{clinic_name}}',
                ['sms', 'whatsapp']],
            ['account.created',
                'تم إنشاء حسابك في {{clinic_name}}. مرحباً بك!',
                'Your account at {{clinic_name}} has been created. Welcome!',
                ['email', 'sms'], 'Welcome to {{clinic_name}}'],
        ];

        foreach ($defs as $def) {
            [$key, $ar, $en, $channels] = $def;
            $subject = $def[4] ?? null;
            foreach ($channels as $channel) {
                $exists = DB::table('notification_templates')
                    ->where('event_key', $key)->where('channel', $channel)->exists();
                if ($exists) {
                    continue; // never clobber an admin-edited template
                }
                DB::table('notification_templates')->insert([
                    'event_key' => $key,
                    'channel' => $channel,
                    'subject' => $channel === 'email' ? $subject : null,
                    'body_ar' => $ar,
                    'body_en' => $en,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Leave templates in place on rollback — they may have been edited.
    }
};

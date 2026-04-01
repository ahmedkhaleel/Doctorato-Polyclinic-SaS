<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── WhatsApp Templates ──────────────────────────
            [
                'name' => 'Welcome - New Lead',
                'channel' => 'whatsapp',
                'category' => 'welcome',
                'subject' => null,
                'body_en' => "Hello {{name}},\n\nThank you for your interest in {{clinic_name}}! We're excited to help you look and feel your best.\n\nWould you like to schedule a consultation? We're available to answer any questions you may have.\n\nBest regards,\n{{clinic_name}}",
                'body_ar' => "مرحبا {{name}}،\n\nشكرا لاهتمامك بـ {{clinic_name}}! نحن متحمسون لمساعدتك.\n\nهل ترغب في حجز استشارة؟ نحن متواجدون للإجابة على أي استفسارات.\n\nمع أطيب التحيات،\n{{clinic_name}}",
                'variables' => ['name', 'clinic_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Appointment Reminder',
                'channel' => 'whatsapp',
                'category' => 'appointment_reminder',
                'subject' => null,
                'body_en' => "Hi {{first_name}},\n\nThis is a friendly reminder about your upcoming appointment at {{clinic_name}}.\n\nPlease arrive 10 minutes early. If you need to reschedule, let us know as soon as possible.\n\nSee you soon!",
                'body_ar' => "مرحبا {{first_name}}،\n\nنود تذكيرك بموعدك القادم في {{clinic_name}}.\n\nيرجى الحضور قبل الموعد بـ 10 دقائق. إذا كنت بحاجة لإعادة الجدولة، أخبرنا في أقرب وقت.\n\nنراك قريبا!",
                'variables' => ['first_name', 'clinic_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Follow-up After Consultation',
                'channel' => 'whatsapp',
                'category' => 'follow_up',
                'subject' => null,
                'body_en' => "Hi {{first_name}},\n\nThank you for visiting {{clinic_name}} today. We hope you had a great experience!\n\nIf you have any questions about the treatment plan discussed, or if you'd like to proceed with booking, feel free to reach out.\n\nWe're here to help!",
                'body_ar' => "مرحبا {{first_name}}،\n\nشكرا لزيارتك {{clinic_name}} اليوم. نأمل أن تكون التجربة ممتازة!\n\nإذا كان لديك أي أسئلة حول خطة العلاج، أو إذا كنت ترغب في الحجز، لا تتردد في التواصل معنا.\n\nنحن هنا لمساعدتك!",
                'variables' => ['first_name', 'clinic_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Special Offer',
                'channel' => 'whatsapp',
                'category' => 'promotion',
                'subject' => null,
                'body_en' => "Hi {{first_name}},\n\nWe have an exclusive offer just for you at {{clinic_name}}!\n\nBook your session this week and enjoy special pricing on our most popular treatments.\n\nContact us now to learn more.\n\nBest,\n{{clinic_name}}",
                'body_ar' => "مرحبا {{first_name}}،\n\nلدينا عرض حصري لك في {{clinic_name}}!\n\nاحجز جلستك هذا الأسبوع واستمتع بأسعار خاصة على أكثر علاجاتنا شعبية.\n\nتواصل معنا الآن لمعرفة المزيد.\n\nمع أطيب التحيات،\n{{clinic_name}}",
                'variables' => ['first_name', 'clinic_name'],
                'is_active' => true,
            ],
            [
                'name' => 'No Response Follow-up',
                'channel' => 'whatsapp',
                'category' => 'follow_up',
                'subject' => null,
                'body_en' => "Hi {{first_name}},\n\nWe tried to reach you earlier but couldn't connect. We'd love to help you with your skin care goals at {{clinic_name}}.\n\nIs there a convenient time for us to chat? We're happy to answer any questions.\n\nWarm regards,\n{{clinic_name}}",
                'body_ar' => "مرحبا {{first_name}}،\n\nحاولنا التواصل معك سابقا ولم نتمكن. نود مساعدتك في تحقيق أهدافك للعناية بالبشرة في {{clinic_name}}.\n\nهل هناك وقت مناسب للتحدث؟ يسعدنا الإجابة على أي أسئلة.\n\nمع أطيب التحيات،\n{{clinic_name}}",
                'variables' => ['first_name', 'clinic_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Re-engagement - Dormant Lead',
                'channel' => 'whatsapp',
                'category' => 're_engagement',
                'subject' => null,
                'body_en' => "Hi {{first_name}},\n\nIt's been a while since we last spoke! We've added exciting new treatments at {{clinic_name}} and thought you might be interested.\n\nWould you like to schedule a free consultation to explore your options?\n\nWe'd love to hear from you!",
                'body_ar' => "مرحبا {{first_name}}،\n\nمر وقت منذ آخر تواصل بيننا! أضفنا علاجات جديدة ومثيرة في {{clinic_name}} وفكرنا أنك قد تكون مهتما.\n\nهل ترغب في حجز استشارة مجانية لاستكشاف خياراتك؟\n\nنتطلع للتواصل معك!",
                'variables' => ['first_name', 'clinic_name'],
                'is_active' => true,
            ],

            // ── Email Templates ─────────────────────────────
            [
                'name' => 'Welcome Email',
                'channel' => 'email',
                'category' => 'welcome',
                'subject' => 'Welcome to {{clinic_name}}',
                'body_en' => "Dear {{name}},\n\nThank you for reaching out to {{clinic_name}}. We specialize in advanced dermatology and aesthetic treatments, and we're dedicated to helping you achieve your skin care goals.\n\nOur team of experienced professionals is ready to provide you with personalized care and the latest treatment options.\n\nTo schedule your consultation, please reply to this email or call us at {{clinic_phone}}.\n\nWe look forward to welcoming you!\n\nWarm regards,\n{{clinic_name}} Team",
                'body_ar' => "عزيزي {{name}}،\n\nشكرا لتواصلك مع {{clinic_name}}. نحن متخصصون في الأمراض الجلدية المتقدمة والعلاجات التجميلية، ونكرس جهودنا لمساعدتك في تحقيق أهدافك للعناية بالبشرة.\n\nفريقنا من المتخصصين ذوي الخبرة مستعد لتقديم رعاية شخصية وأحدث خيارات العلاج.\n\nلحجز استشارتك، يرجى الرد على هذا البريد أو الاتصال بنا على {{clinic_phone}}.\n\nنتطلع لاستقبالك!\n\nمع أطيب التحيات،\nفريق {{clinic_name}}",
                'variables' => ['name', 'clinic_name', 'clinic_phone'],
                'is_active' => true,
            ],
            [
                'name' => 'Consultation Summary',
                'channel' => 'email',
                'category' => 'follow_up',
                'subject' => 'Your Consultation Summary - {{clinic_name}}',
                'body_en' => "Dear {{name}},\n\nThank you for your consultation at {{clinic_name}} on {{date}}.\n\nWe discussed several treatment options tailored to your needs. If you're ready to proceed or have any additional questions, please don't hesitate to contact us.\n\nWe're committed to providing you with the best possible care and results.\n\nBest regards,\n{{clinic_name}} Team",
                'body_ar' => "عزيزي {{name}}،\n\nشكرا لاستشارتك في {{clinic_name}} بتاريخ {{date}}.\n\nناقشنا العديد من خيارات العلاج المصممة خصيصا لاحتياجاتك. إذا كنت مستعدا للمتابعة أو لديك أي أسئلة إضافية، لا تتردد في التواصل معنا.\n\nنحن ملتزمون بتقديم أفضل رعاية ونتائج ممكنة.\n\nمع أطيب التحيات،\nفريق {{clinic_name}}",
                'variables' => ['name', 'clinic_name', 'date'],
                'is_active' => true,
            ],

            // ── SMS Templates ───────────────────────────────
            [
                'name' => 'SMS - Quick Reminder',
                'channel' => 'sms',
                'category' => 'appointment_reminder',
                'subject' => null,
                'body_en' => "Hi {{first_name}}, reminder: you have an appointment at {{clinic_name}}. Please arrive 10 min early. To reschedule, call {{clinic_phone}}.",
                'body_ar' => "مرحبا {{first_name}}، تذكير: لديك موعد في {{clinic_name}}. يرجى الحضور قبل 10 دقائق. لإعادة الجدولة اتصل {{clinic_phone}}.",
                'variables' => ['first_name', 'clinic_name', 'clinic_phone'],
                'is_active' => true,
            ],
            [
                'name' => 'SMS - Thank You',
                'channel' => 'sms',
                'category' => 'thank_you',
                'subject' => null,
                'body_en' => "Thank you for visiting {{clinic_name}}, {{first_name}}! We hope you enjoyed your experience. For follow-up questions, reach us at {{clinic_phone}}.",
                'body_ar' => "شكرا لزيارتك {{clinic_name}}، {{first_name}}! نأمل أن تكون التجربة ممتازة. للاستفسارات تواصل معنا {{clinic_phone}}.",
                'variables' => ['first_name', 'clinic_name', 'clinic_phone'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            CommunicationTemplate::updateOrCreate(
                ['name' => $template['name'], 'channel' => $template['channel']],
                $template,
            );
        }
    }
}

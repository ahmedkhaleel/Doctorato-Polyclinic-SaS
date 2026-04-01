<?php

namespace Database\Seeders;

use App\Models\LeadScoringRule;
use Illuminate\Database\Seeder;

class LeadScoringRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // Positive scoring
            [
                'name_en' => 'Lead Created',
                'name_ar' => 'تم انشاء ليد جديد',
                'event' => 'lead_created',
                'points' => 5,
                'description' => 'Base score when a new lead is created',
            ],
            [
                'name_en' => 'Phone Provided',
                'name_ar' => 'تم تقديم رقم الهاتف',
                'event' => 'phone_provided',
                'points' => 10,
                'description' => 'Lead provided a valid phone number',
            ],
            [
                'name_en' => 'Email Provided',
                'name_ar' => 'تم تقديم البريد',
                'event' => 'email_provided',
                'points' => 5,
                'description' => 'Lead provided an email address',
            ],
            [
                'name_en' => 'Call Answered',
                'name_ar' => 'تم الرد على المكالمة',
                'event' => 'call_answered',
                'points' => 15,
                'description' => 'Lead answered a phone call',
            ],
            [
                'name_en' => 'Call No Answer',
                'name_ar' => 'لم يرد على المكالمة',
                'event' => 'call_no_answer',
                'points' => -5,
                'description' => 'Lead did not answer the call',
            ],
            [
                'name_en' => 'WhatsApp Replied',
                'name_ar' => 'رد على الواتساب',
                'event' => 'whatsapp_replied',
                'points' => 15,
                'description' => 'Lead replied to a WhatsApp message',
            ],
            [
                'name_en' => 'Appointment Booked',
                'name_ar' => 'تم حجز موعد',
                'event' => 'appointment_booked',
                'points' => 25,
                'description' => 'Lead booked an appointment',
            ],
            [
                'name_en' => 'Appointment Attended',
                'name_ar' => 'حضر الموعد',
                'event' => 'appointment_attended',
                'points' => 30,
                'description' => 'Lead attended their appointment',
            ],
            [
                'name_en' => 'Appointment Missed',
                'name_ar' => 'لم يحضر الموعد',
                'event' => 'appointment_missed',
                'points' => -15,
                'description' => 'Lead missed their appointment',
            ],
            [
                'name_en' => 'Visit Completed',
                'name_ar' => 'اكتملت الزيارة',
                'event' => 'visit_completed',
                'points' => 35,
                'description' => 'Lead completed a clinic visit',
            ],
            [
                'name_en' => 'Payment Made',
                'name_ar' => 'تم الدفع',
                'event' => 'payment_made',
                'points' => 40,
                'description' => 'Lead made a payment',
            ],
            [
                'name_en' => 'Interested in Service',
                'name_ar' => 'مهتم بخدمة',
                'event' => 'interested_in_service',
                'points' => 10,
                'description' => 'Lead expressed interest in a specific service',
            ],
            [
                'name_en' => 'Referral Provided',
                'name_ar' => 'قدم إحالة',
                'event' => 'referral_provided',
                'points' => 20,
                'description' => 'Lead referred someone else',
            ],

            [
                'name_en' => 'Follow-up Completed',
                'name_ar' => 'تم اكمال المتابعة',
                'event' => 'follow_up_completed',
                'points' => 10,
                'description' => 'A scheduled follow-up was completed successfully',
            ],
            [
                'name_en' => 'Follow-up Missed',
                'name_ar' => 'تم تفويت المتابعة',
                'event' => 'follow_up_missed',
                'points' => -10,
                'description' => 'A scheduled follow-up was missed',
            ],

            // Negative scoring (decay)
            [
                'name_en' => 'No Response (3 Days)',
                'name_ar' => 'لا استجابة (3 أيام)',
                'event' => 'no_response_3_days',
                'points' => -5,
                'description' => 'No response from lead after 3 days',
            ],
            [
                'name_en' => 'No Response (7 Days)',
                'name_ar' => 'لا استجابة (7 أيام)',
                'event' => 'no_response_7_days',
                'points' => -10,
                'description' => 'No response from lead after 7 days',
            ],
            [
                'name_en' => 'No Response (14 Days)',
                'name_ar' => 'لا استجابة (14 يوم)',
                'event' => 'no_response_14_days',
                'points' => -20,
                'description' => 'No response from lead after 14 days',
            ],
        ];

        foreach ($rules as $rule) {
            LeadScoringRule::updateOrCreate(
                ['event' => $rule['event']],
                $rule
            );
        }
    }
}

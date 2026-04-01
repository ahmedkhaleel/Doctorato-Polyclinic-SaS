<?php

namespace Database\Seeders;

use App\Models\FollowUpSequence;
use App\Models\FollowUpSequenceStep;
use Illuminate\Database\Seeder;

class FollowUpSequenceSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. New Lead Welcome Sequence ─────────────────────
        $seq1 = FollowUpSequence::updateOrCreate(
            ['name' => 'New Lead Welcome'],
            [
                'name_ar' => 'ترحيب بالعميل الجديد',
                'description' => 'Automatically welcomes new leads and schedules initial follow-up',
                'trigger_event' => FollowUpSequence::EVENT_LEAD_CREATED,
                'is_active' => true,
                'stop_on_reply' => true,
                'stop_on_conversion' => true,
            ]
        );

        if ($seq1->steps()->count() === 0) {
            $seq1->steps()->createMany([
                [
                    'step_order' => 1,
                    'delay_minutes' => 30, // 30 minutes after creation
                    'action_type' => FollowUpSequenceStep::ACTION_NOTIFY_STAFF,
                    'notification_message' => 'New lead received! Please contact within 30 minutes.',
                    'is_active' => true,
                ],
                [
                    'step_order' => 2,
                    'delay_minutes' => 60, // 1 hour
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'call',
                    'notification_message' => 'Initial call - introduce clinic services and book consultation',
                    'is_active' => true,
                ],
                [
                    'step_order' => 3,
                    'delay_minutes' => 1440, // 1 day later
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'whatsapp',
                    'notification_message' => 'Send WhatsApp follow-up if no response to initial call',
                    'is_active' => true,
                ],
                [
                    'step_order' => 4,
                    'delay_minutes' => 4320, // 3 days later
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'call',
                    'notification_message' => 'Second call attempt - offer special promotion',
                    'is_active' => true,
                ],
                [
                    'step_order' => 5,
                    'delay_minutes' => 10080, // 7 days later
                    'action_type' => FollowUpSequenceStep::ACTION_ADD_SCORE,
                    'score_points' => -10,
                    'notification_message' => 'No response after 7 days - score decreased',
                    'is_active' => true,
                ],
            ]);
        }

        // ─── 2. Post-Booking Confirmation Sequence ────────────
        $seq2 = FollowUpSequence::updateOrCreate(
            ['name' => 'Booking Confirmation'],
            [
                'name_ar' => 'تأكيد الحجز',
                'description' => 'Confirms booking and sends preparation instructions',
                'trigger_event' => FollowUpSequence::EVENT_BOOKING_CREATED,
                'is_active' => true,
                'stop_on_reply' => false,
                'stop_on_conversion' => true,
            ]
        );

        if ($seq2->steps()->count() === 0) {
            $seq2->steps()->createMany([
                [
                    'step_order' => 1,
                    'delay_minutes' => 5, // 5 minutes after booking
                    'action_type' => FollowUpSequenceStep::ACTION_NOTIFY_STAFF,
                    'notification_message' => 'New booking received! Please confirm the appointment.',
                    'is_active' => true,
                ],
                [
                    'step_order' => 2,
                    'delay_minutes' => 1440, // 1 day before (reminder)
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'whatsapp',
                    'notification_message' => 'Send appointment reminder - confirm attendance',
                    'is_active' => true,
                ],
            ]);
        }

        // ─── 3. Post-Consultation Follow-up ───────────────────
        $seq3 = FollowUpSequence::updateOrCreate(
            ['name' => 'Post-Consultation Follow-up'],
            [
                'name_ar' => 'متابعة بعد الاستشارة',
                'description' => 'Follows up after consultation to encourage treatment booking',
                'trigger_event' => FollowUpSequence::EVENT_STATUS_CONSULTATION,
                'is_active' => true,
                'stop_on_reply' => true,
                'stop_on_conversion' => true,
            ]
        );

        if ($seq3->steps()->count() === 0) {
            $seq3->steps()->createMany([
                [
                    'step_order' => 1,
                    'delay_minutes' => 120, // 2 hours after consultation
                    'action_type' => FollowUpSequenceStep::ACTION_NOTIFY_STAFF,
                    'notification_message' => 'Follow up with patient about consultation results and treatment plan',
                    'is_active' => true,
                ],
                [
                    'step_order' => 2,
                    'delay_minutes' => 2880, // 2 days later
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'call',
                    'notification_message' => 'Call to discuss treatment options and answer questions',
                    'is_active' => true,
                ],
                [
                    'step_order' => 3,
                    'delay_minutes' => 7200, // 5 days later
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'whatsapp',
                    'notification_message' => 'Final follow-up - offer limited-time discount on treatment',
                    'is_active' => true,
                ],
            ]);
        }

        // ─── 4. Missed Follow-up Recovery ─────────────────────
        $seq4 = FollowUpSequence::updateOrCreate(
            ['name' => 'Missed Follow-up Recovery'],
            [
                'name_ar' => 'استعادة المتابعة الفائتة',
                'description' => 'Re-engages leads when a follow-up was missed',
                'trigger_event' => FollowUpSequence::EVENT_FOLLOW_UP_MISSED,
                'is_active' => true,
                'stop_on_reply' => true,
                'stop_on_conversion' => true,
            ]
        );

        if ($seq4->steps()->count() === 0) {
            $seq4->steps()->createMany([
                [
                    'step_order' => 1,
                    'delay_minutes' => 60, // 1 hour after missed
                    'action_type' => FollowUpSequenceStep::ACTION_NOTIFY_STAFF,
                    'notification_message' => 'A follow-up was missed! Please reschedule immediately.',
                    'is_active' => true,
                ],
                [
                    'step_order' => 2,
                    'delay_minutes' => 240, // 4 hours later
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'call',
                    'notification_message' => 'Urgent: rescheduled follow-up call needed',
                    'is_active' => true,
                ],
            ]);
        }

        // ─── 5. Website Contact Form Follow-up ────────────────
        $seq5 = FollowUpSequence::updateOrCreate(
            ['name' => 'Contact Form Response'],
            [
                'name_ar' => 'الرد على نموذج التواصل',
                'description' => 'Quick response sequence for website contact form submissions',
                'trigger_event' => FollowUpSequence::EVENT_CONTACT_FORM,
                'is_active' => true,
                'stop_on_reply' => true,
                'stop_on_conversion' => true,
            ]
        );

        if ($seq5->steps()->count() === 0) {
            $seq5->steps()->createMany([
                [
                    'step_order' => 1,
                    'delay_minutes' => 15, // 15 minutes
                    'action_type' => FollowUpSequenceStep::ACTION_NOTIFY_STAFF,
                    'notification_message' => 'New contact form submission! Respond within 15 minutes.',
                    'is_active' => true,
                ],
                [
                    'step_order' => 2,
                    'delay_minutes' => 60, // 1 hour
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'call',
                    'notification_message' => 'Call the lead who submitted the contact form',
                    'is_active' => true,
                ],
                [
                    'step_order' => 3,
                    'delay_minutes' => 1440, // 1 day
                    'action_type' => FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP,
                    'follow_up_type' => 'whatsapp',
                    'notification_message' => 'WhatsApp follow-up for contact form lead',
                    'is_active' => true,
                ],
            ]);
        }
    }
}

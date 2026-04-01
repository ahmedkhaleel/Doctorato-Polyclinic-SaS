<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE lead_scoring_rules MODIFY COLUMN event ENUM(
            'lead_created', 'phone_provided', 'email_provided',
            'call_answered', 'call_no_answer', 'whatsapp_replied',
            'appointment_booked', 'appointment_attended', 'appointment_missed',
            'visit_completed', 'payment_made',
            'no_response_3_days', 'no_response_7_days', 'no_response_14_days',
            'interested_in_service', 'referral_provided',
            'follow_up_completed', 'follow_up_missed'
        )");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lead_scoring_rules MODIFY COLUMN event ENUM(
            'lead_created', 'phone_provided', 'email_provided',
            'call_answered', 'call_no_answer', 'whatsapp_replied',
            'appointment_booked', 'appointment_attended', 'appointment_missed',
            'visit_completed', 'payment_made',
            'no_response_3_days', 'no_response_7_days', 'no_response_14_days',
            'interested_in_service', 'referral_provided'
        )");
    }
};

<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\FollowUpSequence;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadAssignmentRule;
use App\Models\LeadScoringRule;
use App\Models\LeadSource;
use App\Models\User;
use App\Notifications\NewWebsiteLeadNotification;
use App\Services\Crm\PhoneNormalizer;

class LeadService
{
    /**
     * Auto-create a lead from a contact message submission.
     */
    public static function createFromContactMessage(ContactMessage $message): ?Lead
    {
        // Check if a lead with the same phone or email already exists
        $existingLead = null;
        if ($message->phone) {
            $existingLead = Lead::whereIn('phone', PhoneNormalizer::matchForms($message->phone))->first();
        }
        if (! $existingLead && $message->email) {
            $existingLead = Lead::where('email', $message->email)->first();
        }

        if ($existingLead) {
            // Link the contact message to the existing lead
            $message->update(['lead_id' => $existingLead->id]);

            // Log activity on the existing lead
            LeadActivity::create([
                'lead_id' => $existingLead->id,
                'type' => 'system',
                'subject' => 'New contact form submission',
                'description' => "Subject: {$message->subject}\nMessage: {$message->message}",
                'direction' => 'inbound',
            ]);

            return $existingLead;
        }

        // Find the website lead source
        $websiteSource = LeadSource::where('slug', 'website')->first();

        // Create a new lead
        $lead = Lead::create([
            'full_name' => $message->name,
            'phone' => PhoneNormalizer::normalize($message->phone),
            'email' => $message->email,
            'status' => Lead::STATUS_NEW,
            'priority' => Lead::PRIORITY_WARM,
            'score' => 0,
            'lead_source_id' => $websiteSource?->id,
            'notes' => $message->subject
                ? "Subject: {$message->subject}\n\n{$message->message}"
                : $message->message,
        ]);

        // Link the contact message to the lead
        $message->update(['lead_id' => $lead->id]);

        // Apply initial scoring
        LeadScoringRule::applyToLead($lead, 'lead_created');
        if ($lead->phone) {
            LeadScoringRule::applyToLead($lead, 'phone_provided');
        }
        if ($lead->email) {
            LeadScoringRule::applyToLead($lead, 'email_provided');
        }

        // Auto-assign based on rules
        static::autoAssign($lead);

        // Log activity
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => 'Lead auto-created from website contact form',
            'description' => "Subject: {$message->subject}\nMessage: {$message->message}",
            'direction' => 'inbound',
        ]);

        // Trigger automation sequences
        FollowUpAutomationService::triggerEvent($lead, FollowUpSequence::EVENT_LEAD_CREATED);
        FollowUpAutomationService::triggerEvent($lead, FollowUpSequence::EVENT_CONTACT_FORM);

        // Instant notification to admins
        static::notifyAdminsNewLead($lead, 'contact_form');

        return $lead;
    }

    /**
     * Auto-create a lead from a website booking submission.
     */
    public static function createFromWebsiteBooking(\App\Models\Booking $booking): ?Lead
    {
        // Check if a lead with the same phone or email already exists
        $existingLead = null;
        if ($booking->phone) {
            $existingLead = Lead::whereIn('phone', PhoneNormalizer::matchForms($booking->phone))->first();
        }
        if (! $existingLead && $booking->email) {
            $existingLead = Lead::where('email', $booking->email)->first();
        }

        if ($existingLead) {
            // Link the booking to the existing lead
            $booking->update(['lead_id' => $existingLead->id]);

            // Update lead status to appointment_booked if in early pipeline
            if (in_array($existingLead->status, [Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_QUALIFIED])) {
                $oldStatus = $existingLead->status;
                $existingLead->update(['status' => Lead::STATUS_APPOINTMENT_BOOKED]);
                LeadActivity::logStatusChange($existingLead, $oldStatus, Lead::STATUS_APPOINTMENT_BOOKED);
            }

            LeadActivity::create([
                'lead_id' => $existingLead->id,
                'type' => LeadActivity::TYPE_BOOKING_CREATED,
                'subject' => 'Website booking submitted',
                'description' => 'Service interest via website booking form',
                'direction' => 'inbound',
            ]);

            LeadScoringRule::applyToLead($existingLead, 'appointment_booked');

            return $existingLead;
        }

        // Find the website lead source
        $websiteSource = LeadSource::where('slug', 'website')->first();

        // Determine interested service
        $interestedServices = [];
        if ($booking->service_id) {
            $interestedServices = [$booking->service_id];
        }

        // Create a new lead
        $lead = Lead::create([
            'full_name' => $booking->full_name,
            'phone' => PhoneNormalizer::normalize($booking->phone),
            'email' => $booking->email,
            'module' => $booking->module ?? 'derma',
            'status' => Lead::STATUS_APPOINTMENT_BOOKED,
            'priority' => Lead::PRIORITY_HOT, // Website bookings are hot leads
            'score' => 0,
            'lead_source_id' => $websiteSource?->id,
            'interested_services' => $interestedServices,
            'notes' => $booking->notes,
            'utm_source' => request()?->cookie('utm_source'),
            'utm_medium' => request()?->cookie('utm_medium'),
            'utm_campaign' => request()?->cookie('utm_campaign'),
            'landing_page' => request()?->cookie('landing_page'),
            'ip_address' => request()?->ip(),
        ]);

        // Link the booking to the lead
        $booking->update(['lead_id' => $lead->id]);

        // Apply initial scoring
        LeadScoringRule::applyToLead($lead, 'lead_created');
        if ($lead->phone) {
            LeadScoringRule::applyToLead($lead, 'phone_provided');
        }
        if ($lead->email) {
            LeadScoringRule::applyToLead($lead, 'email_provided');
        }
        LeadScoringRule::applyToLead($lead, 'appointment_booked');

        // Auto-assign based on rules
        static::autoAssign($lead);

        // Log activity
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => LeadActivity::TYPE_BOOKING_CREATED,
            'subject' => 'Lead auto-created from website booking',
            'description' => 'Booked via website booking form',
            'direction' => 'inbound',
        ]);

        // Trigger automation sequences
        FollowUpAutomationService::triggerEvent($lead, FollowUpSequence::EVENT_LEAD_CREATED);
        FollowUpAutomationService::triggerEvent($lead, FollowUpSequence::EVENT_BOOKING_CREATED);
        FollowUpAutomationService::triggerStatusChange($lead, Lead::STATUS_APPOINTMENT_BOOKED);

        // Instant notification to admins
        static::notifyAdminsNewLead($lead, 'booking');

        return $lead;
    }

    /**
     * Try to find and link a lead when a booking is created.
     */
    public static function linkBookingToLead(\App\Models\Booking $booking): void
    {
        if ($booking->lead_id) {
            return;
        } // Already linked

        $lead = null;

        // Try to match by patient phone
        if ($booking->phone) {
            $lead = Lead::whereIn('phone', PhoneNormalizer::matchForms($booking->phone))
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])
                ->first();
        }

        // Try to match by email
        if (! $lead && $booking->email) {
            $lead = Lead::where('email', $booking->email)
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])
                ->first();
        }

        if ($lead) {
            $booking->update(['lead_id' => $lead->id]);

            // Update lead status
            if (in_array($lead->status, [Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_QUALIFIED])) {
                $oldStatus = $lead->status;
                $lead->update(['status' => Lead::STATUS_APPOINTMENT_BOOKED, 'booking_id' => $booking->id]);
                LeadActivity::logStatusChange($lead, $oldStatus, Lead::STATUS_APPOINTMENT_BOOKED);
            }

            LeadActivity::create([
                'lead_id' => $lead->id,
                'type' => LeadActivity::TYPE_BOOKING_CREATED,
                'subject' => 'Booking created',
                'metadata' => ['booking_id' => $booking->id],
            ]);

            LeadScoringRule::applyToLead($lead, 'appointment_booked');

            // Trigger automation sequences
            FollowUpAutomationService::triggerEvent($lead, FollowUpSequence::EVENT_BOOKING_CREATED);
            FollowUpAutomationService::triggerStatusChange($lead, Lead::STATUS_APPOINTMENT_BOOKED);
        }
    }

    /**
     * Auto-assign a lead based on assignment rules.
     */
    public static function autoAssign(Lead $lead): void
    {
        if ($lead->assigned_to) {
            return;
        } // Already assigned

        $rule = LeadAssignmentRule::findMatchingRule($lead);

        if ($rule && $rule->assign_to_user_id) {
            $lead->assignTo($rule->assign_to_user_id);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'type' => 'system',
                'subject' => "Auto-assigned via rule: {$rule->name}",
                'metadata' => ['rule_id' => $rule->id, 'assigned_to' => $rule->assign_to_user_id],
            ]);
        }
    }

    /**
     * Send instant notification to all admin users when a new website lead arrives.
     */
    public static function notifyAdminsNewLead(Lead $lead, string $source = 'website'): void
    {
        $admins = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))
            ->where('is_active', true)
            ->get();

        $notification = new NewWebsiteLeadNotification($lead, $source);

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }
    }
}

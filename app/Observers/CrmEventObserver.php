<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadScoringRule;
use App\Models\Payment;
use App\Models\Visit;

/**
 * Observes clinic events (visits, payments, bookings) and updates the CRM pipeline.
 * This bridges the gap between clinic operations and lead management.
 */
class CrmEventObserver
{
    /**
     * When a visit is marked as completed, update the linked lead.
     */
    public static function onVisitCompleted(Visit $visit): void
    {
        $lead = static::findLeadForVisit($visit);
        if (! $lead) return;

        // Update lead status to consultation_done if in early pipeline
        if (in_array($lead->status, [
            Lead::STATUS_NEW,
            Lead::STATUS_CONTACTED,
            Lead::STATUS_QUALIFIED,
            Lead::STATUS_APPOINTMENT_BOOKED,
        ])) {
            $oldStatus = $lead->status;
            $lead->update(['status' => Lead::STATUS_CONSULTATION_DONE]);
            LeadActivity::logStatusChange($lead, $oldStatus, Lead::STATUS_CONSULTATION_DONE);
        }

        // Apply scoring
        LeadScoringRule::applyToLead($lead, 'visit_completed');

        // Log activity
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => LeadActivity::TYPE_VISIT_COMPLETED,
            'subject' => 'Visit completed',
            'description' => $visit->service?->name_en
                ? "Completed: {$visit->service->name_en}"
                : 'Visit completed at the clinic',
            'direction' => 'inbound',
            'metadata' => [
                'visit_id' => $visit->id,
                'service_id' => $visit->service_id,
                'doctor_id' => $visit->doctor_id,
            ],
        ]);
    }

    /**
     * When a visit is cancelled (patient no-show), penalize the lead.
     */
    public static function onVisitCancelled(Visit $visit): void
    {
        $lead = static::findLeadForVisit($visit);
        if (! $lead) return;

        LeadScoringRule::applyToLead($lead, 'appointment_missed');

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => 'Visit cancelled / no-show',
            'description' => 'Patient did not attend or visit was cancelled.',
            'metadata' => ['visit_id' => $visit->id],
        ]);
    }

    /**
     * When a payment is recorded, update the linked lead.
     */
    public static function onPaymentReceived(Payment $payment): void
    {
        // Find the lead via the invoice's patient
        $lead = static::findLeadForPayment($payment);
        if (! $lead) return;

        // Apply scoring
        LeadScoringRule::applyToLead($lead, 'payment_made');

        // If lead is in negotiation and has a linked patient, auto-convert
        if ($lead->status === Lead::STATUS_NEGOTIATION && $lead->patient_id) {
            $patient = \App\Models\Patient::find($lead->patient_id);
            if ($patient) {
                $oldStatus = $lead->status;
                $lead->convertToPatient($patient);
                LeadActivity::logStatusChange($lead, $oldStatus, Lead::STATUS_CONVERTED);
            }
        }

        // Log activity
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => LeadActivity::TYPE_PAYMENT_RECEIVED,
            'subject' => 'Payment received',
            'description' => "Amount: {$payment->amount}",
            'direction' => 'inbound',
            'metadata' => [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'amount' => $payment->amount,
            ],
        ]);
    }

    /**
     * When a booking status changes (confirmed, attended, no-show).
     */
    public static function onBookingStatusChanged(Booking $booking, string $oldStatus, string $newStatus): void
    {
        $lead = $booking->lead_id ? Lead::find($booking->lead_id) : null;

        if (! $lead) {
            // Try to find lead by phone/email
            $lead = static::findLeadByContact($booking->phone, $booking->email);
        }

        if (! $lead) return;

        // Link booking to lead if not already linked
        if (! $booking->lead_id) {
            $booking->updateQuietly(['lead_id' => $lead->id]);
        }

        // Handle status transitions
        if ($newStatus === 'confirmed' && in_array($lead->status, [Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_QUALIFIED])) {
            $leadOldStatus = $lead->status;
            $lead->update(['status' => Lead::STATUS_APPOINTMENT_BOOKED]);
            LeadActivity::logStatusChange($lead, $leadOldStatus, Lead::STATUS_APPOINTMENT_BOOKED);
            LeadScoringRule::applyToLead($lead, 'appointment_booked');
        }

        if ($newStatus === 'completed') {
            LeadScoringRule::applyToLead($lead, 'appointment_attended');
        }

        if (in_array($newStatus, ['no_show', 'cancelled'])) {
            LeadScoringRule::applyToLead($lead, 'appointment_missed');
        }
    }

    // ─── Helper Methods ─────────────────────────────────

    /**
     * Find a lead linked to a visit (via booking -> lead, or patient -> lead).
     */
    protected static function findLeadForVisit(Visit $visit): ?Lead
    {
        // Try via booking
        if ($visit->booking_id) {
            $booking = Booking::find($visit->booking_id);
            if ($booking?->lead_id) {
                return Lead::find($booking->lead_id);
            }
        }

        // Try via patient
        if ($visit->patient_id) {
            $lead = Lead::where('patient_id', $visit->patient_id)
                ->whereNotIn('status', [Lead::STATUS_LOST])
                ->first();
            if ($lead) return $lead;

            // Try by patient phone/email
            $patient = $visit->patient;
            if ($patient) {
                return static::findLeadByContact($patient->phone, $patient->email);
            }
        }

        return null;
    }

    /**
     * Find a lead linked to a payment (via invoice -> patient -> lead).
     */
    protected static function findLeadForPayment(Payment $payment): ?Lead
    {
        $invoice = $payment->invoice;
        if (! $invoice?->patient_id) return null;

        // Direct link
        $lead = Lead::where('patient_id', $invoice->patient_id)
            ->whereNotIn('status', [Lead::STATUS_LOST])
            ->first();

        if ($lead) return $lead;

        // Fallback: by patient phone/email
        $patient = $invoice->patient;
        if ($patient) {
            return static::findLeadByContact($patient->phone, $patient->email);
        }

        return null;
    }

    /**
     * Find a lead by phone or email.
     */
    protected static function findLeadByContact(?string $phone, ?string $email): ?Lead
    {
        if ($phone) {
            $lead = Lead::where('phone', $phone)
                ->whereNotIn('status', [Lead::STATUS_LOST])
                ->first();
            if ($lead) return $lead;
        }

        if ($email) {
            $lead = Lead::where('email', $email)
                ->whereNotIn('status', [Lead::STATUS_LOST])
                ->first();
            if ($lead) return $lead;
        }

        return null;
    }
}

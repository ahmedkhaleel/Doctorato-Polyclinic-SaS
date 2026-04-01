<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCrmOnPayment implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        $patient = $event->payment->patient;
        if (! $patient) {
            return;
        }

        // Find linked lead via email or phone
        $lead = Lead::where(function ($q) use ($patient) {
            if ($patient->email) {
                $q->orWhere('email', $patient->email);
            }
            if ($patient->phone) {
                $q->orWhere('phone', $patient->phone);
            }
        })->whereNotIn('status', ['converted', 'lost'])->first();

        if (! $lead) {
            return;
        }

        // Log payment activity on the lead
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'payment',
            'title' => 'Payment received',
            'description' => "Payment of {$event->payment->amount} SAR received. Invoice #{$event->invoice->invoice_number}",
        ]);

        // If invoice fully paid, boost lead score
        if ($event->invoice->status === 'paid') {
            $lead->increment('score', 20);
        }
    }
}

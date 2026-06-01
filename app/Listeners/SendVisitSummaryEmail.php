<?php

namespace App\Listeners;

use App\Events\VisitCompleted;
use App\Notifications\VisitSummaryEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Email a tidy visit summary to the patient after the visit is marked
 * completed. Best-effort: failures log but never break the visit
 * completion flow.
 */
class SendVisitSummaryEmail
{
    public function handle(VisitCompleted $event): void
    {
        $visit = $event->visit;
        $patient = $visit->patient;

        if (! $patient || ! $patient->email) {
            return;
        }
        if (! $patient->wantsNotification('bookings', 'email')) {
            return;
        }

        try {
            // Eager-load relations the email template reads from.
            $visit->loadMissing([
                'doctor:id,name_ar,name_en',
                'service:id,name_ar,name_en',
                'invoice:id,visit_id',
                'prescriptions.items',
            ]);

            Notification::route('mail', $patient->email)
                ->notify(new VisitSummaryEmail($visit));
        } catch (\Throwable $e) {
            Log::warning('[visit.summary.email] failed', [
                'visit_id' => $visit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

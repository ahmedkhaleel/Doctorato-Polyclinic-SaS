<?php

namespace App\Listeners;

use App\Events\VisitCompleted;
use App\Jobs\SendSmsJob;
use App\Models\LoyaltyPoint;
use App\Models\Setting;
use App\Models\SmsTemplate;
use App\Services\LoyaltyService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

/**
 * Award loyalty points to the patient when a visit is marked completed.
 * Idempotent: refuses to credit twice for the same Visit row.
 */
class AwardLoyaltyOnVisit
{
    public function handle(VisitCompleted $event): void
    {
        $visit = $event->visit;
        if (! $visit->patient_id) return;

        // Idempotency — has this visit already credited points?
        $alreadyCredited = LoyaltyPoint::where('reference_type', $visit->getMorphClass())
            ->where('reference_id', $visit->id)
            ->where('type', LoyaltyPoint::TYPE_EARN)
            ->exists();
        if ($alreadyCredited) return;

        try {
            $billed = $visit->total_amount ?? $visit->fee ?? null;
            $points = LoyaltyService::pointsForVisit($billed ? (float) $billed : null);
            if ($points <= 0) return;

            $patient = $visit->patient;
            if (! $patient) return;

            LoyaltyService::award(
                $patient,
                $points,
                "Visit #{$visit->id} on " . ($visit->visit_date ?? now()->toDateString()),
                $visit
            );

            // Best-effort SMS — only if patient opted in and SMS is enabled.
            // Uses the admin-editable `loyalty_earned` template, falling
            // back silently if the template is missing or SMS is disabled.
            $this->sendEarnedSms($patient, $points);

        } catch (\Throwable $e) {
            Log::warning('[loyalty.award] failed', [
                'visit_id' => $visit->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function sendEarnedSms($patient, int $points): void
    {
        try {
            if (! SmsService::isEnabled()) return;
            if (! $patient->phone) return;
            if (! $patient->wantsNotification('marketing', 'sms')) return;
            if (Setting::get('sms_on_loyalty_earned', '1') !== '1') return;

            $balance   = LoyaltyService::balance($patient);
            $egpValue  = LoyaltyService::pointsToEgp($balance);
            $currency  = Setting::get('currency_code', 'EGP');
            $locale    = $patient->preferred_language ?? 'ar';

            $body = SmsTemplate::render('loyalty_earned', [
                'points'    => $points,
                'balance'   => $balance,
                'egp_value' => number_format($egpValue, 0) . ' ' . $currency,
            ], $locale);

            if (! $body) return;

            SendSmsJob::dispatch($patient->phone, $body, null, 'loyalty_earned');
        } catch (\Throwable $e) {
            Log::warning('[loyalty.earned.sms] failed', [
                'patient_id' => $patient->id ?? null,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}

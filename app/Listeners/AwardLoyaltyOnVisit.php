<?php

namespace App\Listeners;

use App\Events\VisitCompleted;
use App\Models\LoyaltyPoint;
use App\Services\LoyaltyService;
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
        } catch (\Throwable $e) {
            Log::warning('[loyalty.award] failed', [
                'visit_id' => $visit->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}

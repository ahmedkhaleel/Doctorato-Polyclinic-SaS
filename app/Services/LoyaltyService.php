<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\Patient;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Loyalty points: earn on visits, redeem on bookings.
 *
 * Earning rules (admin-tunable via Settings):
 *   - loyalty_points_per_egp     : how many points per EGP spent
 *                                  (default 1 — i.e. 100 EGP = 100 points)
 *   - loyalty_points_per_visit   : flat bonus per completed visit
 *                                  (default 50)
 *   - loyalty_redeem_rate        : EGP per point on redemption
 *                                  (default 0.10 — i.e. 100 pts = 10 EGP)
 *   - loyalty_min_redeem_points  : minimum balance to redeem
 *                                  (default 100)
 *   - loyalty_expiry_months      : earned points expire after N months
 *                                  (default 12, set 0 to never expire)
 */
class LoyaltyService
{
    private const DEFAULT_PTS_PER_EGP   = 1;
    private const DEFAULT_PTS_PER_VISIT = 50;
    private const DEFAULT_REDEEM_RATE   = 0.10;
    private const DEFAULT_MIN_REDEEM    = 100;
    private const DEFAULT_EXPIRY_MONTHS = 12;

    public static function balance(Patient $patient): int
    {
        return (int) LoyaltyPoint::where('patient_id', $patient->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('points');
    }

    /**
     * Credit points to a patient. Returns the LoyaltyPoint row.
     */
    public static function award(
        Patient $patient,
        int $points,
        string $description = '',
        ?Model $reference = null,
        ?int $adminUserId = null,
    ): LoyaltyPoint {
        if ($points <= 0) {
            throw new \InvalidArgumentException('award() requires a positive points value');
        }

        $expiryMonths = (int) Setting::get('loyalty_expiry_months', self::DEFAULT_EXPIRY_MONTHS);

        return LoyaltyPoint::create([
            'patient_id'    => $patient->id,
            'points'        => $points,
            'type'          => LoyaltyPoint::TYPE_EARN,
            'description'   => $description,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id'  => $reference?->getKey(),
            'expires_at'    => $expiryMonths > 0 ? now()->addMonths($expiryMonths) : null,
            'admin_user_id' => $adminUserId,
        ]);
    }

    /**
     * Debit points from a patient's balance. Returns the LoyaltyPoint
     * row, or null if the patient doesn't have enough.
     */
    public static function redeem(
        Patient $patient,
        int $points,
        string $description = '',
        ?Model $reference = null,
    ): ?LoyaltyPoint {
        if ($points <= 0) {
            throw new \InvalidArgumentException('redeem() requires a positive points value');
        }

        $minRedeem = (int) Setting::get('loyalty_min_redeem_points', self::DEFAULT_MIN_REDEEM);
        if ($points < $minRedeem) return null;

        return DB::transaction(function () use ($patient, $points, $description, $reference) {
            $balance = self::balance($patient);
            if ($balance < $points) return null;

            return LoyaltyPoint::create([
                'patient_id'     => $patient->id,
                'points'         => -$points,
                'type'           => LoyaltyPoint::TYPE_REDEEM,
                'description'    => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id'   => $reference?->getKey(),
            ]);
        });
    }

    /**
     * Manual admin adjustment (positive or negative). Always recorded
     * with the admin user who made the change.
     */
    public static function adjust(Patient $patient, int $points, string $reason, int $adminUserId): LoyaltyPoint
    {
        return LoyaltyPoint::create([
            'patient_id'    => $patient->id,
            'points'        => $points,
            'type'          => LoyaltyPoint::TYPE_ADJUST,
            'description'   => $reason,
            'admin_user_id' => $adminUserId,
        ]);
    }

    /**
     * Convert points to EGP-equivalent at the configured rate.
     */
    public static function pointsToEgp(int $points): float
    {
        $rate = (float) Setting::get('loyalty_redeem_rate', self::DEFAULT_REDEEM_RATE);
        return round($points * $rate, 2);
    }

    /**
     * Convert an EGP discount the patient wants → points required.
     */
    public static function egpToPoints(float $egp): int
    {
        $rate = (float) Setting::get('loyalty_redeem_rate', self::DEFAULT_REDEEM_RATE);
        if ($rate <= 0) return 0;
        return (int) ceil($egp / $rate);
    }

    /**
     * Compute the points to award for a visit. Used by the
     * VisitCompleted listener.
     */
    public static function pointsForVisit(?float $billedAmount = null): int
    {
        $perEgp   = (float) Setting::get('loyalty_points_per_egp', self::DEFAULT_PTS_PER_EGP);
        $perVisit = (int)   Setting::get('loyalty_points_per_visit', self::DEFAULT_PTS_PER_VISIT);

        $fromAmount = $billedAmount !== null ? (int) floor($billedAmount * $perEgp) : 0;
        return max(0, $fromAmount + $perVisit);
    }
}

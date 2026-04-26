<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\LoyaltyPoint;
use App\Models\Patient;
use App\Models\PatientReferral;
use App\Models\Setting;

/**
 * Single source of truth for the "patient engagement" snapshot shown
 * on staff-facing patient pages: loyalty balance, active discount
 * codes, referral counts, etc.
 *
 * Lightweight by design — staff Show pages already render a lot of
 * data, this is meant to be one small additional payload.
 */
class PatientEngagementService
{
    /**
     * Build the payload for the engagement card.
     *
     * Shape:
     *   loyalty.balance, .egp_value, .currency, .min_redeem
     *   loyalty.active_codes[] = [code, amount, type, expires_at]
     *   referral.count, .total_discount, .currency
     */
    public static function forPatient(Patient $patient): array
    {
        $balance   = LoyaltyService::balance($patient);
        $minRedeem = (int) Setting::get('loyalty_min_redeem_points', 100);
        $currency  = Setting::get('currency_code', 'EGP');

        // Active discount codes — issued via redemptions or referrals.
        // Polymorphic ref on the redeem ledger row points back to the
        // DiscountCode, so we can find the patient's codes without
        // relying on prefix matching.
        $codeIds = LoyaltyPoint::where('patient_id', $patient->id)
            ->where('type', LoyaltyPoint::TYPE_REDEEM)
            ->where('reference_type', (new DiscountCode)->getMorphClass())
            ->pluck('reference_id')
            ->all();

        // Plus FRIEND/THANKS codes from referrals (their codes are stored
        // in patient_referrals.code, not via the loyalty ledger).
        $referralCodes = PatientReferral::where('referrer_patient_id', $patient->id)
            ->orWhere('referred_patient_id', $patient->id)
            ->pluck('code')
            ->filter()
            ->all();

        // Bail early if the patient has no personal codes — otherwise an
        // empty WHERE-IN clause silently broadens the query to every
        // active discount code in the system (including clinic-wide
        // promotional codes that don't belong to this patient).
        $activeCodes = [];
        if ($codeIds || $referralCodes) {
            $activeCodes = DiscountCode::query()
                ->where(function ($q) use ($codeIds, $referralCodes) {
                    if ($codeIds)       $q->orWhereIn('id', $codeIds);
                    if ($referralCodes) $q->orWhereIn('code', $referralCodes);
                })
                ->where('is_active', true)
                ->whereColumn('used_count', '<', 'max_uses')
                ->where(function ($q) { $q->whereNull('end_date')->orWhere('end_date', '>=', now()); })
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'code', 'discount_value', 'discount_type', 'end_date'])
                ->map(fn ($d) => [
                    'code'       => $d->code,
                    'amount'     => (float) $d->discount_value,
                    'type'       => $d->discount_type, // 'fixed' | 'percentage'
                    'expires_at' => $d->end_date?->toDateString(),
                ])
                ->all();
        }

        $referralStats = PatientReferralService::statsFor($patient);

        return [
            'loyalty' => [
                'balance'      => $balance,
                'egp_value'    => LoyaltyService::pointsToEgp($balance),
                'currency'     => $currency,
                'min_redeem'   => $minRedeem,
                'eligible_to_redeem' => $balance >= $minRedeem,
                'active_codes' => $activeCodes,
            ],
            'referral' => [
                'count'          => $referralStats['count'],
                'total_discount' => $referralStats['total_discount'],
                'currency'       => $referralStats['currency'],
            ],
        ];
    }
}

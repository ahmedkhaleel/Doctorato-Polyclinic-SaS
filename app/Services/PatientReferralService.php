<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientReferral;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Patient-to-patient referrals.
 *
 * Flow:
 *   1. Sara registers → patients.referral_code is auto-generated (e.g. SARA-7K9X)
 *      via Patient::booted() hook.
 *   2. Sara shares the code with Lina.
 *   3. Lina registers and types the code, OR enters it on her first booking
 *      page → PatientReferralService::redeem() is called.
 *   4. Service validates: referrer exists, Lina hasn't already been
 *      referred, the code isn't her own, and creates the referrals row.
 *
 * Refund/credit policy:
 *   - The friend (Lina) gets the discount on their first booking via
 *     a generated DiscountCode.
 *   - The referrer (Sara) gets a clinic-wallet credit OR a thank-you
 *     code, whichever the admin configures.
 *
 * For the v1 ship: just track the relationship. Apply discounts via
 * existing DiscountCode flow at booking time.
 */
class PatientReferralService
{
    /**
     * Default discount values, overridable by Settings.
     */
    private const DEFAULT_DISCOUNT_AMOUNT = 50;   // EGP off referred friend's first booking
    private const DEFAULT_CURRENCY        = 'EGP';

    /**
     * Find a patient by their shareable referral code.
     */
    public static function findReferrer(string $code): ?Patient
    {
        $code = trim(strtoupper($code));
        if ($code === '') return null;

        return Patient::where('referral_code', $code)->first();
    }

    /**
     * Validate + create a referral row when a new patient redeems a code.
     * Returns the created PatientReferral or null if not eligible.
     *
     * Rejection cases (each logs why for debugging):
     *  - referrer not found
     *  - referrer === referred (self-referral)
     *  - referred patient was already referred by someone
     */
    public static function redeem(string $code, Patient $newPatient, ?int $bookingId = null): ?PatientReferral
    {
        $referrer = self::findReferrer($code);

        if (! $referrer) {
            Log::info('[referral] code not found', ['code' => $code]);
            return null;
        }
        if ($referrer->id === $newPatient->id) {
            Log::info('[referral] self-referral blocked', ['patient_id' => $newPatient->id]);
            return null;
        }
        if (PatientReferral::where('referred_patient_id', $newPatient->id)->exists()) {
            Log::info('[referral] patient already referred', ['patient_id' => $newPatient->id]);
            return null;
        }

        $discount = (float) Setting::get('referral_discount_amount', self::DEFAULT_DISCOUNT_AMOUNT);
        $currency = Setting::get('currency_code', self::DEFAULT_CURRENCY);

        return PatientReferral::create([
            'referrer_patient_id' => $referrer->id,
            'referred_patient_id' => $newPatient->id,
            'code'                => strtoupper(trim($code)),
            'discount_amount'     => $discount,
            'discount_currency'   => $currency,
            'first_booking_id'    => $bookingId,
            'redeemed_at'         => now(),
        ]);
    }

    /**
     * Stats for a single patient: how many they've referred, and how
     * much discount has been earned on their behalf.
     */
    public static function statsFor(Patient $patient): array
    {
        $referrals = PatientReferral::where('referrer_patient_id', $patient->id);

        return [
            'count'              => (clone $referrals)->count(),
            'total_discount'     => (clone $referrals)->sum('discount_amount'),
            'currency'           => Setting::get('currency_code', self::DEFAULT_CURRENCY),
            'recent'             => (clone $referrals)->latest('redeemed_at')
                                        ->limit(5)
                                        ->with('referred:id,full_name')
                                        ->get(['id', 'referred_patient_id', 'discount_amount', 'redeemed_at']),
        ];
    }

    /**
     * Build a shareable URL the patient can paste into WhatsApp/SMS.
     */
    public static function shareUrl(Patient $patient, string $locale = 'ar'): string
    {
        $base = rtrim(config('app.url'), '/');
        return "{$base}/{$locale}/patient/register?ref={$patient->referral_code}";
    }
}

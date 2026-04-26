<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\PatientReferral;
use App\Notifications\ReferralRewardEmail;
use App\Services\PatientReferralService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * When a referred patient creates their first booking:
 *  1. Issue a single-use FRIEND-XXXXXX discount for the friend
 *  2. Issue a THANKS-XXXXXX thank-you for the referrer
 *  3. Attach this booking_id to the referral row
 *  4. Notify both parties via email
 *
 * No-op if:
 *  - The booking has no patient_id (anonymous walk-in)
 *  - The patient has no referral row
 *  - The referral was already consumed (first_booking_id set)
 */
class ApplyReferralReward
{
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;
        if (! $booking->patient_id) return;

        $referral = PatientReferral::where('referred_patient_id', $booking->patient_id)
            ->whereNull('first_booking_id')
            ->with('referrer', 'referred')
            ->first();
        if (! $referral) return;

        try {
            $friendDiscount = PatientReferralService::issueFriendDiscount($referral->referred);
            $thankYou       = PatientReferralService::issueReferrerThankYou($referral->referrer);
            PatientReferralService::attachFirstBooking($referral->referred, $booking->id);

            // Notify both — best-effort.
            if ($friendDiscount && $referral->referred?->email) {
                Notification::route('mail', $referral->referred->email)
                    ->notify(new ReferralRewardEmail('friend', $friendDiscount->code, $friendDiscount->discount_value, $referral->referred));
            }
            if ($thankYou && $referral->referrer?->email) {
                Notification::route('mail', $referral->referrer->email)
                    ->notify(new ReferralRewardEmail('referrer', $thankYou->code, $thankYou->discount_value, $referral->referrer));
            }
        } catch (\Throwable $e) {
            Log::warning('[apply-referral-reward] failed', [
                'referral_id' => $referral->id,
                'booking_id'  => $booking->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}

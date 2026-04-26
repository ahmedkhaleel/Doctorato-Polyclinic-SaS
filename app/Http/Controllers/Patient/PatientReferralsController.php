<?php

namespace App\Http\Controllers\Patient;

use App\Models\PatientReferral;
use App\Services\PatientReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Full-page view of a patient's referrals: their share code, list of
 * friends they've referred, total reward earned, and the share URL.
 */
class PatientReferralsController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);
        $locale  = $request->route('locale', 'ar');

        $stats = PatientReferralService::statsFor($patient);

        $referrals = PatientReferral::where('referrer_patient_id', $patient->id)
            ->with('referred:id,full_name')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn ($r) => [
                'id'              => $r->id,
                'referred_name'   => $r->referred?->full_name ?? '-',
                'discount_amount' => (float) $r->discount_amount,
                'currency'        => $r->discount_currency,
                'redeemed_at'     => $r->redeemed_at?->toDateString(),
                'created_at'      => $r->created_at?->toDateString(),
            ]);

        return Inertia::render('Patient/Referrals/Index', [
            'code'           => $patient->referral_code,
            'share_url'      => PatientReferralService::shareUrl($patient, $locale),
            'count'          => $stats['count'],
            'total_discount' => $stats['total_discount'],
            'currency'       => $stats['currency'],
            'referrals'      => $referrals,
        ]);
    }
}

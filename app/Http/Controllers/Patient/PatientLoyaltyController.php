<?php

namespace App\Http\Controllers\Patient;

use App\Models\LoyaltyPoint;
use App\Models\Setting;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Full-page view of a patient's loyalty ledger:
 * balance, EGP equivalent, redemption rules, full transaction history.
 */
class PatientLoyaltyController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        $balance = LoyaltyService::balance($patient);
        $minRedeem = (int) Setting::get('loyalty_min_redeem_points', 100);
        $perEgp    = (float) Setting::get('loyalty_points_per_egp', 1);
        $perVisit  = (int)   Setting::get('loyalty_points_per_visit', 50);
        $expiry    = (int)   Setting::get('loyalty_expiry_months', 12);

        $transactions = LoyaltyPoint::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($r) => [
                'id'          => $r->id,
                'points'      => $r->points,
                'type'        => $r->type,
                'description' => $r->description,
                'expires_at'  => $r->expires_at?->toDateString(),
                'created_at'  => $r->created_at?->toDateTimeString(),
            ]);

        // Per-type aggregate (sum of positive points by type) for the stat cards.
        $stats = [
            'total_earned'   => (int) LoyaltyPoint::where('patient_id', $patient->id)
                                  ->where('type', LoyaltyPoint::TYPE_EARN)->sum('points'),
            'total_redeemed' => (int) abs(LoyaltyPoint::where('patient_id', $patient->id)
                                  ->where('type', LoyaltyPoint::TYPE_REDEEM)->sum('points')),
            'total_expired'  => (int) abs(LoyaltyPoint::where('patient_id', $patient->id)
                                  ->where('type', LoyaltyPoint::TYPE_EXPIRE)->sum('points')),
        ];

        return Inertia::render('Patient/Loyalty/Index', [
            'balance'      => $balance,
            'egp_value'    => LoyaltyService::pointsToEgp($balance),
            'currency'     => Setting::get('currency_code', 'EGP'),
            'min_redeem'   => $minRedeem,
            'rules' => [
                'points_per_egp'   => $perEgp,
                'points_per_visit' => $perVisit,
                'expiry_months'    => $expiry,
                'redeem_rate'      => (float) Setting::get('loyalty_redeem_rate', 0.10),
            ],
            'stats'        => $stats,
            'transactions' => $transactions,
        ]);
    }
}

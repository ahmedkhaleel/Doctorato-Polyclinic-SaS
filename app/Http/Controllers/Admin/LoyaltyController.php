<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\LoyaltyService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin oversight for the loyalty points system:
 *  - List patients with their balances
 *  - View any patient's full ledger
 *  - Manually adjust (+/-) a patient's points with a reason
 *
 * All adjustments are logged with the admin user id for audit trail.
 */
class LoyaltyController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        // Aggregate balance per patient (active points = points where expires_at IS NULL or > now)
        $balanceSubquery = DB::table('loyalty_points')
            ->select('patient_id', DB::raw('SUM(points) as balance'))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->groupBy('patient_id');

        $patientsQ = Patient::query()
            ->leftJoinSub($balanceSubquery, 'lp', 'lp.patient_id', '=', 'patients.id')
            ->select('patients.id', 'patients.full_name', 'patients.file_number', 'patients.phone',
                     DB::raw('COALESCE(lp.balance, 0) as balance'));

        if ($search !== '') {
            $patientsQ->where(function ($q) use ($search) {
                $q->where('patients.full_name', 'like', "%$search%")
                  ->orWhere('patients.file_number', 'like', "%$search%")
                  ->orWhere('patients.phone', 'like', "%$search%");
            });
        }

        $patients = $patientsQ->orderByDesc('balance')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($p) => [
                'id'          => $p->id,
                'full_name'   => $p->full_name,
                'file_number' => $p->file_number,
                'phone'       => $p->phone,
                'balance'     => (int) $p->balance,
                'egp_value'   => LoyaltyService::pointsToEgp((int) $p->balance),
            ]);

        // Top-line metrics
        $stats = [
            'total_outstanding' => (int) LoyaltyPoint::where(function ($q) {
                                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                        })->sum('points'),
            'patients_with_pts' => (int) DB::table('loyalty_points')
                                            ->select(DB::raw('COUNT(DISTINCT patient_id) as c'))
                                            ->where(function ($q) {
                                                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                            })
                                            ->value('c'),
            'awarded_30d'       => (int) LoyaltyPoint::where('type', LoyaltyPoint::TYPE_EARN)
                                            ->where('created_at', '>=', now()->subDays(30))
                                            ->sum('points'),
            'redeemed_30d'      => (int) abs(LoyaltyPoint::where('type', LoyaltyPoint::TYPE_REDEEM)
                                            ->where('created_at', '>=', now()->subDays(30))
                                            ->sum('points')),
        ];

        $rules = [
            'points_per_egp'   => (float) Setting::get('loyalty_points_per_egp', 1),
            'points_per_visit' => (int)   Setting::get('loyalty_points_per_visit', 50),
            'redeem_rate'      => (float) Setting::get('loyalty_redeem_rate', 0.10),
            'min_redeem'       => (int)   Setting::get('loyalty_min_redeem_points', 100),
            'expiry_months'    => (int)   Setting::get('loyalty_expiry_months', 12),
            'currency'         => Setting::get('currency_code', 'EGP'),
        ];

        return Inertia::render('Admin/Loyalty/Index', [
            'patients' => $patients,
            'stats'    => $stats,
            'rules'    => $rules,
            'filters'  => ['search' => $search],
        ]);
    }

    public function show(Patient $patient): Response
    {
        $balance = LoyaltyService::balance($patient);

        $transactions = LoyaltyPoint::where('patient_id', $patient->id)
            ->with('admin:id,name')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->through(fn ($r) => [
                'id'           => $r->id,
                'points'       => $r->points,
                'type'         => $r->type,
                'description'  => $r->description,
                'expires_at'   => $r->expires_at?->toDateString(),
                'admin_name'   => $r->admin?->name,
                'created_at'   => $r->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Loyalty/Show', [
            'patient' => $patient->only(['id', 'full_name', 'file_number', 'phone']),
            'balance'      => $balance,
            'egp_value'    => LoyaltyService::pointsToEgp($balance),
            'currency'     => Setting::get('currency_code', 'EGP'),
            'transactions' => $transactions,
        ]);
    }

    public function adjust(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $entry = LoyaltyService::adjust(
            $patient,
            (int) $data['points'],
            $data['reason'],
            (int) $request->user()->id,
        );

        AuditLogger::log('loyalty_adjusted', $patient, [
            'points'  => $entry->points,
            'reason'  => $entry->description,
            'entry_id' => $entry->id,
        ]);

        return back()->with('success', 'Loyalty balance adjusted.');
    }
}

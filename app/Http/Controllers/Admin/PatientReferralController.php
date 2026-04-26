<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatientReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin oversight for the patient-to-patient referral program.
 * View who's referring whom, total reward issued, and per-referral
 * details. Read-only — referrals are auto-issued by the system.
 */
class PatientReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $q = PatientReferral::query()
            ->with([
                'referrer:id,full_name,file_number,phone',
                'referred:id,full_name,file_number,phone',
                'firstBooking:id,booking_number,status',
            ])
            ->orderByDesc('created_at');

        if ($search !== '') {
            $q->whereHas('referrer', fn ($x) => $x->where('full_name', 'like', "%$search%")
                                                    ->orWhere('file_number', 'like', "%$search%"))
              ->orWhereHas('referred', fn ($x) => $x->where('full_name', 'like', "%$search%")
                                                    ->orWhere('file_number', 'like', "%$search%"))
              ->orWhere('code', 'like', "%$search%");
        }

        $referrals = $q->paginate(20)
            ->withQueryString()
            ->through(fn ($r) => [
                'id'                  => $r->id,
                'code'                => $r->code,
                'referrer_name'       => $r->referrer?->full_name ?? '—',
                'referrer_id'         => $r->referrer_patient_id,
                'referrer_file'       => $r->referrer?->file_number,
                'referred_name'       => $r->referred?->full_name ?? '—',
                'referred_id'         => $r->referred_patient_id,
                'referred_file'       => $r->referred?->file_number,
                'discount_amount'     => (float) $r->discount_amount,
                'discount_currency'   => $r->discount_currency,
                'first_booking_id'    => $r->first_booking_id,
                'first_booking_no'    => $r->firstBooking?->booking_number,
                'redeemed_at'         => $r->redeemed_at?->toDateString(),
                'created_at'          => $r->created_at?->toDateString(),
            ]);

        // Top-line metrics
        $stats = [
            'total_referrals'    => (int) PatientReferral::count(),
            'total_redeemed'     => (int) PatientReferral::whereNotNull('redeemed_at')->count(),
            'total_discount'     => (float) PatientReferral::sum('discount_amount'),
            'this_month'         => (int) PatientReferral::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        // Top 5 referrers
        $topReferrers = PatientReferral::select('referrer_patient_id', DB::raw('COUNT(*) as cnt'),
                            DB::raw('SUM(discount_amount) as total'))
            ->groupBy('referrer_patient_id')
            ->orderByDesc('cnt')
            ->limit(5)
            ->with('referrer:id,full_name,file_number')
            ->get()
            ->map(fn ($row) => [
                'patient_id'     => $row->referrer_patient_id,
                'name'           => $row->referrer?->full_name ?? '—',
                'file_number'    => $row->referrer?->file_number,
                'count'          => (int) $row->cnt,
                'total_discount' => (float) $row->total,
            ]);

        return Inertia::render('Admin/PatientReferrals/Index', [
            'referrals'    => $referrals,
            'stats'        => $stats,
            'topReferrers' => $topReferrers,
            'filters'      => ['search' => $search],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DoctorPayout;
use App\Models\Visit;
use App\Services\ModuleManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DoctorCommissionController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctor = $this->doctor($request);
        $doctorId = $doctor->id;
        $now = Carbon::now();
        $enabledModules = array_keys(ModuleManager::getActiveModules());

        // ─── Summary ─────────────────────────────────────────
        $thisMonthCommission = Visit::where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereMonth('visit_date', $now->month)
            ->whereYear('visit_date', $now->year)
            ->whereIn('module', $enabledModules)
            ->sum('commission_amount');

        $lastMonthCommission = Visit::where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereMonth('visit_date', $now->copy()->subMonth()->month)
            ->whereYear('visit_date', $now->copy()->subMonth()->year)
            ->whereIn('module', $enabledModules)
            ->sum('commission_amount');

        $totalCommission = Visit::where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereIn('module', $enabledModules)
            ->sum('commission_amount');

        $summary = [
            'this_month' => round((float) $thisMonthCommission, 2),
            'last_month' => round((float) $lastMonthCommission, 2),
            'total' => round((float) $totalCommission, 2),
        ];

        // ─── Payout Summary ──────────────────────────────────
        $totalPaid = round((float) DoctorPayout::where('doctor_id', $doctorId)
            ->paid()->sum('net_amount'), 2);

        $totalPending = round((float) DoctorPayout::where('doctor_id', $doctorId)
            ->confirmed()->sum('net_amount'), 2);

        $totalInDraft = round((float) DoctorPayout::where('doctor_id', $doctorId)
            ->draft()->sum('net_amount'), 2);

        // Unpaid = total earned minus anything in active payouts
        $totalInActivePayouts = round((float) DoctorPayout::where('doctor_id', $doctorId)
            ->active()->sum('net_amount'), 2);
        $totalUnpaid = round(max($summary['total'] - $totalInActivePayouts, 0), 2);

        $payoutSummary = [
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'total_draft' => $totalInDraft,
            'total_unpaid' => $totalUnpaid,
        ];

        // ─── Recent Payouts ──────────────────────────────────
        $recentPayouts = DoctorPayout::where('doctor_id', $doctorId)
            ->select('id', 'payout_number', 'period_start', 'period_end', 'net_amount', 'status', 'paid_at', 'created_at')
            ->latest()
            ->take(5)
            ->get();

        // ─── Monthly Trend (last 6 months) ────────────────────
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $amount = Visit::where('doctor_id', $doctorId)
                ->where('status', 'completed')
                ->whereMonth('visit_date', $month->month)
                ->whereYear('visit_date', $month->year)
                ->whereIn('module', $enabledModules)
                ->sum('commission_amount');

            $monthlyTrend[] = [
                'label' => $month->format('M'),
                'value' => round((float) $amount, 2),
            ];
        }

        // ─── Detailed Commission Visits ───────────────────────
        $query = Visit::with(['patient:id,full_name', 'service:id,name_en'])
            ->where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereNotNull('commission_amount')
            ->where('commission_amount', '>', 0)
            ->whereIn('module', $enabledModules);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom || $dateTo) {
            if ($dateFrom) {
                $query->whereDate('visit_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('visit_date', '<=', $dateTo);
            }
        } elseif ($month = $request->input('month')) {
            $date = Carbon::parse($month);
            $query->whereMonth('visit_date', $date->month)
                ->whereYear('visit_date', $date->year);
        } else {
            $query->whereMonth('visit_date', $now->month)
                ->whereYear('visit_date', $now->year);
        }

        // Clone query before pagination to get visit IDs for payout status lookup
        $visitIds = (clone $query)->pluck('id');

        $visits = $query->latest('visit_date')->paginate(15)->withQueryString();

        // Attach payout status to each visit
        $visitPayoutMap = DB::table('doctor_payout_visits')
            ->join('doctor_payouts', 'doctor_payouts.id', '=', 'doctor_payout_visits.doctor_payout_id')
            ->whereIn('doctor_payout_visits.visit_id', $visits->pluck('id'))
            ->whereIn('doctor_payouts.status', ['draft', 'confirmed', 'paid'])
            ->select('doctor_payout_visits.visit_id', 'doctor_payouts.status as payout_status', 'doctor_payouts.id as payout_id', 'doctor_payouts.payout_number')
            ->get()
            ->keyBy('visit_id');

        $visits->getCollection()->transform(function ($visit) use ($visitPayoutMap) {
            $payoutInfo = $visitPayoutMap->get($visit->id);
            $visit->payout_status = $payoutInfo?->payout_status ?? null;
            $visit->payout_id = $payoutInfo?->payout_id ?? null;
            $visit->payout_number = $payoutInfo?->payout_number ?? null;
            return $visit;
        });

        // ─── Commission Rates Info ──────────────────────────
        $doctor->load('serviceRates.service:id,name_en,name_ar');
        $commissionInfo = [
            'default_rate' => (float) ($doctor->default_commission_percentage ?? 0),
            'service_rates' => $doctor->serviceRates->map(fn ($sr) => [
                'service_name' => $sr->service->name_en ?? $sr->service->name_ar ?? '-',
                'rate' => (float) $sr->commission_percentage,
            ]),
        ];

        return Inertia::render('Doctor/Commission/Index', [
            'summary' => $summary,
            'payoutSummary' => $payoutSummary,
            'recentPayouts' => $recentPayouts,
            'monthlyTrend' => $monthlyTrend,
            'visits' => $visits,
            'commissionInfo' => $commissionInfo,
            'filters' => $request->only(['month', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show payout detail for the doctor (read-only, scoped to their own payouts).
     */
    public function payoutShow(Request $request, DoctorPayout $payout): Response
    {
        $doctor = $this->doctor($request);

        // Ensure the payout belongs to this doctor
        if ($payout->doctor_id !== $doctor->id) {
            abort(403, 'This payout does not belong to you.');
        }

        $payout->load([
            'doctor:id,name_en,name_ar,default_commission_percentage',
            'visits.patient:id,full_name',
            'visits.service:id,name_en',
            'paidByUser:id,name',
        ]);

        return Inertia::render('Doctor/Commission/PayoutShow', [
            'payout' => $payout,
        ]);
    }

    /**
     * Print payout receipt for the doctor (only paid payouts).
     */
    public function payoutPrint(Request $request, DoctorPayout $payout): Response
    {
        $doctor = $this->doctor($request);

        if ($payout->doctor_id !== $doctor->id) {
            abort(403, 'This payout does not belong to you.');
        }

        if ($payout->status !== 'paid') {
            abort(403, 'Receipt is only available for paid payouts.');
        }

        $payout->load([
            'doctor:id,name_en,name_ar',
            'visits.patient:id,full_name',
            'visits.service:id,name_en',
            'paidByUser:id,name',
        ]);

        return Inertia::render('Doctor/Commission/PayoutReceipt', [
            'payout' => $payout,
        ]);
    }
}

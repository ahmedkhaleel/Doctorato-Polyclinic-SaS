<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorPayout;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDoctorPayoutController extends Controller
{
    /**
     * List all payouts with filters and summary.
     */
    public function index(Request $request): Response
    {
        $query = DoctorPayout::with(['doctor:id,name_en,name_ar', 'createdByUser:id,name']);

        if ($module = $request->input('module')) {
            $query->whereHas('visits', fn ($q) => $q->where('module', $module));
        }

        if ($doctorId = $request->input('doctor_id')) {
            $query->where('doctor_id', $doctorId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $payouts = $query->latest()->paginate(15)->withQueryString();

        // Summary cards
        $summary = [
            'total_draft' => round((float) DoctorPayout::draft()->sum('net_amount'), 2),
            'total_confirmed' => round((float) DoctorPayout::confirmed()->sum('net_amount'), 2),
            'total_paid_this_month' => round((float) DoctorPayout::paid()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('net_amount'), 2),
            'total_outstanding' => round((float) DoctorPayout::whereIn('status', ['draft', 'confirmed'])->sum('net_amount'), 2),
            // Extended stats
            'total_paid_all_time' => round((float) DoctorPayout::paid()->sum('net_amount'), 2),
            'count_draft' => DoctorPayout::draft()->count(),
            'count_confirmed' => DoctorPayout::confirmed()->count(),
            'count_paid' => DoctorPayout::paid()->count(),
            'count_cancelled' => DoctorPayout::cancelled()->count(),
            'count_total' => DoctorPayout::count(),
            'avg_payout_amount' => round((float) DoctorPayout::paid()->avg('net_amount'), 2),
            'total_deductions' => round((float) DoctorPayout::whereIn('status', ['draft', 'confirmed', 'paid'])->sum('deductions'), 2),
            'total_visits_covered' => (int) DoctorPayout::whereIn('status', ['draft', 'confirmed', 'paid'])->sum('total_visits'),
        ];

        // Top doctors by paid amount (for sidebar/widget)
        $topDoctors = DoctorPayout::select('doctor_id', DB::raw('SUM(net_amount) as total_paid'), DB::raw('COUNT(*) as payout_count'))
            ->where('status', 'paid')
            ->groupBy('doctor_id')
            ->orderByDesc('total_paid')
            ->limit(5)
            ->with('doctor:id,name_en')
            ->get()
            ->map(fn ($item) => [
                'doctor_name' => $item->doctor?->name_en,
                'total_paid' => round((float) $item->total_paid, 2),
                'payout_count' => $item->payout_count,
            ]);

        // Recent activity (latest 5 status changes)
        $recentActivity = DoctorPayout::with('doctor:id,name_en')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'payout_number' => $p->payout_number,
                'doctor_name' => $p->doctor?->name_en,
                'status' => $p->status,
                'net_amount' => $p->net_amount,
                'updated_at' => $p->updated_at?->diffForHumans(),
                'paid_at' => $p->paid_at?->format('Y-m-d'),
                'created_at' => $p->created_at?->format('Y-m-d'),
            ]);

        $doctors = Doctor::active()->select('id', 'name_en', 'name_ar')->orderBy('name_en')->get();

        return Inertia::render('Admin/DoctorPayouts/Index', [
            'payouts' => $payouts,
            'summary' => $summary,
            'doctors' => $doctors,
            'topDoctors' => $topDoctors,
            'recentActivity' => $recentActivity,
            'filters' => $request->only(['doctor_id', 'status', 'date_from', 'date_to', 'module']),
        ]);
    }

    /**
     * Show create payout form.
     */
    public function create(Request $request): Response
    {
        $doctors = Doctor::active()
            ->select('id', 'name_en', 'name_ar', 'default_commission_percentage')
            ->orderBy('name_en')
            ->get();

        // Pre-load visits if doctor_id is given via query string
        $unpaidVisits = [];
        if ($doctorId = $request->input('doctor_id')) {
            $unpaidVisits = $this->fetchUnpaidVisits($doctorId, $request->input('date_from'), $request->input('date_to'));
        }

        return Inertia::render('Admin/DoctorPayouts/Create', [
            'doctors' => $doctors,
            'unpaidVisits' => $unpaidVisits,
            'filters' => $request->only(['doctor_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Store a new payout (status=draft).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'visit_ids' => 'required|array|min:1',
            'visit_ids.*' => 'exists:visits,id',
            'deductions' => 'nullable|numeric|min:0',
            'deduction_notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Verify all visits belong to the doctor and are unpaid
        $visits = Visit::where('doctor_id', $request->doctor_id)
            ->where('status', 'completed')
            ->where('commission_amount', '>', 0)
            ->whereIn('id', $request->visit_ids)
            ->whereDoesntHave('payouts', function ($q) {
                $q->whereIn('doctor_payouts.status', ['draft', 'confirmed', 'paid']);
            })
            ->get();

        if ($visits->isEmpty()) {
            return back()->withErrors(['visit_ids' => 'No valid unpaid visits found for the selected criteria.']);
        }

        $deductions = (float) ($request->deductions ?? 0);
        $totalRevenue = $visits->sum(function ($v) {
            return (float) $v->commission_amount / ((float) $v->commission_rate / 100);
        });
        $totalCommission = (float) $visits->sum('commission_amount');
        $netAmount = $totalCommission - $deductions;

        $payout = DB::transaction(function () use ($request, $visits, $totalRevenue, $totalCommission, $deductions, $netAmount) {
            $payout = DoctorPayout::create([
                'payout_number' => DoctorPayout::generatePayoutNumber(),
                'doctor_id' => $request->doctor_id,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'total_visits' => $visits->count(),
                'total_revenue' => round($totalRevenue, 2),
                'total_commission' => round($totalCommission, 2),
                'deductions' => $deductions,
                'deduction_notes' => $request->deduction_notes,
                'net_amount' => round(max($netAmount, 0), 2),
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Attach visits to pivot with commission snapshots
            $pivotData = [];
            foreach ($visits as $visit) {
                // Calculate per-visit amount
                $rate = (float) $visit->commission_rate;
                $commissionAmount = (float) $visit->commission_amount;
                $visitAmount = $rate > 0 ? round($commissionAmount / ($rate / 100), 2) : 0;

                $pivotData[$visit->id] = [
                    'visit_amount' => $visitAmount,
                    'commission_rate' => $rate,
                    'commission_amount' => $commissionAmount,
                ];
            }
            $payout->visits()->attach($pivotData);

            return $payout;
        });

        AuditLogger::log('created', $payout);

        return redirect()->route('admin.doctor-payouts.show', $payout)
            ->with('success', 'Payout created successfully as draft.');
    }

    /**
     * Show payout details.
     */
    public function show(DoctorPayout $payout): Response
    {
        $payout->load([
            'doctor:id,name_en,name_ar,default_commission_percentage',
            'visits.patient:id,full_name',
            'visits.service:id,name_en',
            'createdByUser:id,name',
            'confirmedByUser:id,name',
            'paidByUser:id,name',
            'cancelledByUser:id,name',
        ]);

        return Inertia::render('Admin/DoctorPayouts/Show', [
            'payout' => $payout,
        ]);
    }

    /**
     * Confirm a draft payout.
     */
    public function confirm(DoctorPayout $payout): RedirectResponse
    {
        if ($payout->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft payouts can be confirmed.']);
        }

        $payout->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ]);

        AuditLogger::log('confirmed', $payout);

        return back()->with('success', 'Payout confirmed successfully.');
    }

    /**
     * Mark a confirmed payout as paid.
     */
    public function markPaid(Request $request, DoctorPayout $payout): RedirectResponse
    {
        if ($payout->status !== 'confirmed') {
            return back()->withErrors(['status' => 'Only confirmed payouts can be marked as paid.']);
        }

        // Hybrid payment model: a salary-mode doctor's commission is paid via
        // the monthly salary slip, NOT cash-disbursed here. Blocking this is
        // what prevents paying the same commission twice.
        if (optional($payout->doctor)->payment_mode === \App\Models\Doctor::PAY_SALARY) {
            return back()->withErrors(['status' => 'هذا الطبيب يُدفع له عبر الرواتب (وضع الراتب) — العمولة تُضاف لقسيمة الراتب الشهرية ولا تُصرف هنا. / This doctor is paid via payroll (salary mode); commission goes onto the monthly salary slip — not disbursed here.']);
        }

        $request->validate([
            'payment_method' => 'required|string|in:cash,bank_transfer,check,mobile_wallet',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => auth()->id(),
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
        ]);

        AuditLogger::log('marked_paid', $payout);

        return back()->with('success', 'Payout marked as paid successfully.');
    }

    /**
     * Cancel a draft or confirmed payout — releases visits back to unpaid pool.
     */
    public function cancel(Request $request, DoctorPayout $payout): RedirectResponse
    {
        if (!in_array($payout->status, ['draft', 'confirmed'])) {
            return back()->withErrors(['status' => 'Only draft or confirmed payouts can be cancelled.']);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $payout->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        AuditLogger::log('cancelled', $payout);

        return back()->with('success', 'Payout cancelled. Visits returned to unpaid pool.');
    }

    /**
     * Print receipt for a paid payout.
     */
    public function printReceipt(DoctorPayout $payout): Response
    {
        if ($payout->status !== 'paid') {
            abort(403, 'Receipt is only available for paid payouts.');
        }

        $payout->load([
            'doctor:id,name_en,name_ar',
            'visits.patient:id,full_name',
            'visits.service:id,name_en',
            'paidByUser:id,name',
        ]);

        return Inertia::render('Admin/DoctorPayouts/PrintReceipt', [
            'payout' => $payout,
        ]);
    }

    /**
     * API: Get unpaid visits for a doctor (used by Create page AJAX).
     */
    public function getUnpaidVisits(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $visits = $this->fetchUnpaidVisits(
            $request->doctor_id,
            $request->date_from,
            $request->date_to
        );

        return response()->json($visits);
    }

    /**
     * Fetch unpaid visits for a doctor.
     */
    protected function fetchUnpaidVisits(int $doctorId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Visit::with(['patient:id,full_name', 'service:id,name_en'])
            ->where('doctor_id', $doctorId)
            ->unpaidCommission();

        if ($dateFrom) {
            $query->whereDate('visit_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('visit_date', '<=', $dateTo);
        }

        return $query->orderBy('visit_date', 'desc')->get()->map(function ($visit) {
            $rate = (float) $visit->commission_rate;
            $commissionAmount = (float) $visit->commission_amount;
            $visitAmount = $rate > 0 ? round($commissionAmount / ($rate / 100), 2) : 0;

            return [
                'id' => $visit->id,
                'visit_date' => $visit->visit_date->format('Y-m-d'),
                'visit_type' => $visit->visit_type,
                'patient_name' => $visit->patient?->full_name,
                'service_name' => $visit->service?->name_en ?? ($visit->visit_type === 'consultation' ? 'Consultation' : 'Session'),
                'visit_amount' => $visitAmount,
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
            ];
        })->values()->toArray();
    }
}

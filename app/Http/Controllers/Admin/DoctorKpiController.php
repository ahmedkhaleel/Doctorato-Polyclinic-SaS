<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DoctorKpiController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $prevFrom = now()->subMonth()->startOfMonth()->toDateString();
        $prevTo = now()->subMonth()->endOfMonth()->toDateString();

        $doctors = Doctor::where('status', 'active')->get();

        $kpis = $doctors->map(function (Doctor $doc) use ($dateFrom, $dateTo, $prevFrom, $prevTo) {
            $baseVisits = fn ($from, $to) => Visit::where('doctor_id', $doc->id)
                ->where('status', 'completed')
                ->whereDate('visit_date', '>=', $from)
                ->whereDate('visit_date', '<=', $to);

            // Visit counts
            $currentVisits = $baseVisits($dateFrom, $dateTo)->count();
            $prevVisits = $baseVisits($prevFrom, $prevTo)->count();

            // Revenue
            $currentRevenue = (float) DB::table('payments')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->join('visits', 'invoices.visit_id', '=', 'visits.id')
                ->where('visits.doctor_id', $doc->id)
                ->whereBetween('payments.payment_date', [$dateFrom, $dateTo])
                ->sum('payments.amount');

            $prevRevenue = (float) DB::table('payments')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->join('visits', 'invoices.visit_id', '=', 'visits.id')
                ->where('visits.doctor_id', $doc->id)
                ->whereBetween('payments.payment_date', [$prevFrom, $prevTo])
                ->sum('payments.amount');

            // Avg service duration
            $avgService = (float) $baseVisits($dateFrom, $dateTo)
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg')
                ->value('avg');

            // Avg wait time
            $avgWait = (float) $baseVisits($dateFrom, $dateTo)
                ->whereNotNull('actual_wait_minutes')
                ->avg('actual_wait_minutes');

            // Patient retention (returning patients / unique patients)
            $uniquePatients = $baseVisits($dateFrom, $dateTo)->distinct('patient_id')->count('patient_id');
            $returningPatients = (int) $baseVisits($dateFrom, $dateTo)
                ->whereIn('patient_id', function ($q) use ($doc, $dateFrom) {
                    $q->select('patient_id')
                        ->from('visits')
                        ->where('doctor_id', $doc->id)
                        ->where('status', 'completed')
                        ->whereDate('visit_date', '<', $dateFrom);
                })
                ->distinct('patient_id')
                ->count('patient_id');
            $retentionRate = $uniquePatients > 0 ? round(($returningPatients / $uniquePatients) * 100, 1) : 0;

            // Commission earned
            $commission = (float) $baseVisits($dateFrom, $dateTo)
                ->whereNotNull('commission_amount')
                ->sum('commission_amount');

            // Satisfaction rating (table may not exist yet)
            try {
                $satisfaction = DB::table('patient_satisfaction_surveys')
                    ->where('doctor_id', $doc->id)
                    ->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo)
                    ->selectRaw('ROUND(AVG(overall_rating), 1) as avg_rating, COUNT(*) as count')
                    ->first();
            } catch (\Exception $e) {
                $satisfaction = null;
            }

            // Cancellation rate
            $totalScheduled = Visit::where('doctor_id', $doc->id)
                ->whereDate('visit_date', '>=', $dateFrom)
                ->whereDate('visit_date', '<=', $dateTo)
                ->count();
            $cancelled = Visit::where('doctor_id', $doc->id)
                ->whereDate('visit_date', '>=', $dateFrom)
                ->whereDate('visit_date', '<=', $dateTo)
                ->where('status', 'cancelled')
                ->count();
            $cancellationRate = $totalScheduled > 0 ? round(($cancelled / $totalScheduled) * 100, 1) : 0;

            return [
                'id' => $doc->id,
                'name_en' => $doc->name_en,
                'name_ar' => $doc->name_ar,
                'specialty_en' => $doc->specialty_en,
                'specialty_ar' => $doc->specialty_ar,
                'department' => $doc->department,
                'visits' => ['current' => $currentVisits, 'previous' => $prevVisits],
                'revenue' => ['current' => round($currentRevenue, 2), 'previous' => round($prevRevenue, 2)],
                'avg_revenue_per_visit' => $currentVisits > 0 ? round($currentRevenue / $currentVisits, 2) : 0,
                'avg_service_minutes' => round($avgService, 1),
                'avg_wait_minutes' => round($avgWait, 1),
                'unique_patients' => $uniquePatients,
                'retention_rate' => $retentionRate,
                'commission' => round($commission, 2),
                'rating' => $satisfaction?->avg_rating ?? 0,
                'rating_count' => $satisfaction?->count ?? 0,
                'cancellation_rate' => $cancellationRate,
            ];
        })->sortByDesc(fn ($d) => $d['revenue']['current'])->values();

        // Aggregate stats
        $totalRevenue = $kpis->sum(fn ($d) => $d['revenue']['current']);
        $totalVisits = $kpis->sum(fn ($d) => $d['visits']['current']);

        return Inertia::render('Admin/Reports/DoctorKpi', [
            'doctors' => $kpis,
            'totals' => [
                'revenue' => round($totalRevenue, 2),
                'visits' => $totalVisits,
                'avg_rating' => $kpis->where('rating', '>', 0)->avg('rating') ? round($kpis->where('rating', '>', 0)->avg('rating'), 1) : 0,
                'avg_retention' => $kpis->where('unique_patients', '>', 0)->avg('retention_rate') ? round($kpis->where('unique_patients', '>', 0)->avg('retention_rate'), 1) : 0,
            ],
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DentalChart;
use App\Models\DentalLabOrder;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\Patient;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DentalReportController extends Controller
{
    /**
     * Dental reports dashboard — revenue, lab, treatment progress, chart stats.
     */
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // ── Previous period for comparison ────────────────────────
        $daysDiff = \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo));
        $prevDateTo = \Carbon\Carbon::parse($dateFrom)->subDay()->toDateString();
        $prevDateFrom = \Carbon\Carbon::parse($prevDateTo)->subDays($daysDiff)->toDateString();

        $prevTreatmentRevenue = DentalTreatment::whereDate('created_at', '>=', $prevDateFrom)
            ->whereDate('created_at', '<=', $prevDateTo)
            ->where('status', 'completed')
            ->selectRaw('SUM(cost) as treatment_cost, SUM(lab_cost) as lab_cost, COUNT(*) as count')
            ->first();

        $prevConsultationRevenue = Visit::where('module', 'dental')
            ->whereDate('visit_date', '>=', $prevDateFrom)
            ->whereDate('visit_date', '<=', $prevDateTo)
            ->where('status', 'completed')
            ->join('invoices', 'visits.id', '=', 'invoices.visit_id')
            ->sum('invoices.paid_amount');

        $prevLabOrders = DentalLabOrder::whereDate('order_date', '>=', $prevDateFrom)
            ->whereDate('order_date', '<=', $prevDateTo)
            ->selectRaw('COUNT(*) as count, SUM(cost) as total_cost')
            ->first();

        $prevTreatmentCount = DentalTreatment::whereDate('created_at', '>=', $prevDateFrom)
            ->whereDate('created_at', '<=', $prevDateTo)
            ->count();

        // ── Revenue ───────────────────────────────────────────────
        $treatmentRevenue = DentalTreatment::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'completed')
            ->selectRaw('SUM(cost) as treatment_cost, SUM(lab_cost) as lab_cost, COUNT(*) as count')
            ->first();

        $consultationRevenue = Visit::where('module', 'dental')
            ->whereDate('visit_date', '>=', $dateFrom)
            ->whereDate('visit_date', '<=', $dateTo)
            ->where('status', 'completed')
            ->join('invoices', 'visits.id', '=', 'invoices.visit_id')
            ->sum('invoices.paid_amount');

        $labOrderCosts = DentalLabOrder::whereDate('order_date', '>=', $dateFrom)
            ->whereDate('order_date', '<=', $dateTo)
            ->selectRaw('SUM(cost) as total_cost, SUM(patient_charge) as total_charge, COUNT(*) as count')
            ->first();

        // Revenue by treatment type
        $revenueByType = DentalTreatment::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'completed')
            ->select('treatment_type', DB::raw('SUM(cost + lab_cost) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('treatment_type')
            ->orderByDesc('total')
            ->get();

        // Monthly revenue trend (last 6 months) — cached 30 min, rarely changes
        $monthlyRevenue = Cache::remember('dental_report_monthly_revenue', 1800, function () {
            return DentalTreatment::where('status', 'completed')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                    DB::raw('SUM(cost) as treatment_revenue'),
                    DB::raw('SUM(lab_cost) as lab_revenue'),
                    DB::raw('COUNT(*) as treatments_count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        });

        // Revenue by doctor
        $revenueByDoctor = DentalTreatment::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'completed')
            ->join('doctors', 'dental_treatments.doctor_id', '=', 'doctors.id')
            ->select(
                'doctors.id',
                'doctors.name_en',
                'doctors.name_ar',
                'doctors.photo',
                DB::raw('SUM(dental_treatments.cost + dental_treatments.lab_cost) as total_revenue'),
                DB::raw('COUNT(*) as treatments_count')
            )
            ->groupBy('doctors.id', 'doctors.name_en', 'doctors.name_ar', 'doctors.photo')
            ->orderByDesc('total_revenue')
            ->get();

        // ── Lab Performance ───────────────────────────────────────
        $labStats = DentalLabOrder::whereDate('order_date', '>=', $dateFrom)
            ->whereDate('order_date', '<=', $dateTo)
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ('delivered','completed') THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN expected_date IS NOT NULL AND expected_date < CURDATE() AND status NOT IN ('delivered','completed') THEN 1 ELSE 0 END) as overdue,
                AVG(CASE WHEN delivered_date IS NOT NULL AND order_date IS NOT NULL THEN DATEDIFF(delivered_date, order_date) END) as avg_delivery_days
            ")
            ->first();

        // Lab performance by lab name
        $labPerformance = DentalLabOrder::whereDate('order_date', '>=', $dateFrom)
            ->whereDate('order_date', '<=', $dateTo)
            ->select(
                'lab_name',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN status IN ('delivered','completed') THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN expected_date IS NOT NULL AND expected_date < CURDATE() AND status NOT IN ('delivered','completed') THEN 1 ELSE 0 END) as overdue"),
                DB::raw('AVG(CASE WHEN delivered_date IS NOT NULL THEN DATEDIFF(delivered_date, order_date) END) as avg_days'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('SUM(patient_charge) as total_charge')
            )
            ->groupBy('lab_name')
            ->orderByDesc('total_orders')
            ->get();

        // Lab orders by status
        $labByStatus = DentalLabOrder::whereDate('order_date', '>=', $dateFrom)
            ->whereDate('order_date', '<=', $dateTo)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // ── Treatment Progress ────────────────────────────────────
        $treatmentStats = DentalTreatment::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as planned,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        // Treatment plans overview
        $planStats = DentalTreatmentPlan::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('approved','in_progress') THEN 1 ELSE 0 END) as active,
                SUM(estimated_cost) as estimated_total,
                SUM(actual_cost) as actual_total
            ")
            ->first();

        // Top patients by treatments count
        $topPatients = DentalTreatment::whereDate('dental_treatments.created_at', '>=', $dateFrom)
            ->whereDate('dental_treatments.created_at', '<=', $dateTo)
            ->join('patients', 'dental_treatments.patient_id', '=', 'patients.id')
            ->select(
                'patients.id',
                'patients.full_name',
                'patients.file_number',
                DB::raw('COUNT(*) as treatments_count'),
                DB::raw('SUM(dental_treatments.cost + dental_treatments.lab_cost) as total_cost'),
                DB::raw("SUM(CASE WHEN dental_treatments.status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            )
            ->groupBy('patients.id', 'patients.full_name', 'patients.file_number')
            ->orderByDesc('treatments_count')
            ->limit(10)
            ->get();

        // ── Treatment Type Breakdown (for current period) ─────────
        $treatmentTypeBreakdown = DentalTreatment::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(
                'treatment_type',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress"),
                DB::raw("SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as planned"),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('SUM(lab_cost) as total_lab_cost'),
                DB::raw('AVG(cost) as avg_cost')
            )
            ->groupBy('treatment_type')
            ->orderByDesc('total')
            ->get();

        // ── Follow-up Metrics ──────────────────────────────────────
        $followupStats = DentalScheduledFollowup::whereDate('scheduled_date', '>=', $dateFrom)
            ->whereDate('scheduled_date', '<=', $dateTo)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' AND booking_id IS NULL THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'pending' AND booking_id IS NULL AND scheduled_date < CURDATE() THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN booking_id IS NOT NULL THEN 1 ELSE 0 END) as booked,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        $followupBookingRate = ($followupStats->total ?? 0) > 0
            ? round((($followupStats->booked ?? 0) + ($followupStats->completed ?? 0)) / $followupStats->total * 100)
            : 0;

        // ── Doctor Productivity ──────────────────────────────────
        $doctorProductivity = DentalTreatment::whereDate('dental_treatments.created_at', '>=', $dateFrom)
            ->whereDate('dental_treatments.created_at', '<=', $dateTo)
            ->join('doctors', 'dental_treatments.doctor_id', '=', 'doctors.id')
            ->select(
                'doctors.id',
                'doctors.name_en',
                'doctors.name_ar',
                'doctors.photo',
                DB::raw('COUNT(*) as total_treatments'),
                DB::raw("SUM(CASE WHEN dental_treatments.status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN dental_treatments.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress"),
                DB::raw('SUM(dental_treatments.cost) as treatment_revenue'),
                DB::raw('SUM(dental_treatments.lab_cost) as lab_cost'),
                DB::raw('AVG(CASE WHEN dental_treatments.completed_at IS NOT NULL AND dental_treatments.created_at IS NOT NULL THEN DATEDIFF(dental_treatments.completed_at, dental_treatments.created_at) END) as avg_completion_days')
            )
            ->groupBy('doctors.id', 'doctors.name_en', 'doctors.name_ar', 'doctors.photo')
            ->orderByDesc('total_treatments')
            ->get();

        // ── Patient Retention: returning patients ────────────────
        $patientRetention = Cache::remember('dental_report_retention:' . $dateFrom . ':' . $dateTo, 900, function () use ($dateFrom, $dateTo) {
            $totalPatients = Visit::where('module', 'dental')
                ->whereDate('visit_date', '>=', $dateFrom)
                ->whereDate('visit_date', '<=', $dateTo)
                ->distinct('patient_id')
                ->count('patient_id');

            $returningPatients = DB::table('visits as v1')
                ->where('v1.module', 'dental')
                ->whereDate('v1.visit_date', '>=', $dateFrom)
                ->whereDate('v1.visit_date', '<=', $dateTo)
                ->whereExists(function ($q) use ($dateFrom) {
                    $q->select(DB::raw(1))
                      ->from('visits as v2')
                      ->where('v2.module', 'dental')
                      ->whereColumn('v2.patient_id', 'v1.patient_id')
                      ->whereDate('v2.visit_date', '<', $dateFrom);
                })
                ->distinct('v1.patient_id')
                ->count('v1.patient_id');

            return [
                'total_patients' => $totalPatients,
                'returning_patients' => $returningPatients,
                'new_patients' => $totalPatients - $returningPatients,
                'retention_rate' => $totalPatients > 0
                    ? round(($returningPatients / $totalPatients) * 100)
                    : 0,
            ];
        });

        // ── Risk Patients Count — cached 15 min (expensive subquery) ──
        $riskPatientsCount = Cache::remember('dental_report_risk_patients', 900, function () {
            return Patient::where(function ($q) {
                $q->where('latex_allergy', true)
                  ->orWhere('anesthesia_complications', true)
                  ->orWhere('has_bleeding_disorder', true)
                  ->orWhere('takes_blood_thinners', true)
                  ->orWhere('has_heart_condition', true)
                  ->orWhere('has_hepatitis', true)
                  ->orWhere('has_hiv', true)
                  ->orWhere('is_pregnant', true);
            })->whereHas('visits', fn ($q) => $q->where('module', 'dental'))->count();
        });

        // ── Dental Chart Overview (all-time) — cached 30 min ──────
        $chartStats = Cache::remember('dental_report_chart_stats', 1800, function () {
            return DentalChart::select('condition', DB::raw('COUNT(*) as count'))
                ->groupBy('condition')
                ->get();
        });

        return Inertia::render('Admin/Reports/Dental', [
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],

            // Revenue
            'treatmentRevenue' => [
                'treatment_cost' => round((float) ($treatmentRevenue->treatment_cost ?? 0), 2),
                'lab_cost' => round((float) ($treatmentRevenue->lab_cost ?? 0), 2),
                'count' => (int) ($treatmentRevenue->count ?? 0),
            ],
            'consultationRevenue' => round((float) $consultationRevenue, 2),
            'labOrderCosts' => [
                'total_cost' => round((float) ($labOrderCosts->total_cost ?? 0), 2),
                'total_charge' => round((float) ($labOrderCosts->total_charge ?? 0), 2),
                'count' => (int) ($labOrderCosts->count ?? 0),
            ],

            // Previous period comparison
            'prevPeriod' => [
                'treatment_revenue' => round((float) ($prevTreatmentRevenue->treatment_cost ?? 0) + (float) ($prevTreatmentRevenue->lab_cost ?? 0), 2),
                'consultation_revenue' => round((float) $prevConsultationRevenue, 2),
                'treatment_count' => (int) $prevTreatmentCount,
                'lab_orders' => (int) ($prevLabOrders->count ?? 0),
                'lab_cost' => round((float) ($prevLabOrders->total_cost ?? 0), 2),
                'date_from' => $prevDateFrom,
                'date_to' => $prevDateTo,
            ],
            'revenueByType' => $revenueByType,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueByDoctor' => $revenueByDoctor,

            // Lab
            'labStats' => [
                'total_orders' => (int) ($labStats->total_orders ?? 0),
                'delivered' => (int) ($labStats->delivered ?? 0),
                'overdue' => (int) ($labStats->overdue ?? 0),
                'avg_delivery_days' => round((float) ($labStats->avg_delivery_days ?? 0), 1),
            ],
            'labPerformance' => $labPerformance,
            'labByStatus' => $labByStatus,

            // Treatments
            'treatmentStats' => [
                'total' => (int) ($treatmentStats->total ?? 0),
                'completed' => (int) ($treatmentStats->completed ?? 0),
                'in_progress' => (int) ($treatmentStats->in_progress ?? 0),
                'planned' => (int) ($treatmentStats->planned ?? 0),
                'cancelled' => (int) ($treatmentStats->cancelled ?? 0),
            ],
            'planStats' => [
                'total' => (int) ($planStats->total ?? 0),
                'completed' => (int) ($planStats->completed ?? 0),
                'active' => (int) ($planStats->active ?? 0),
                'estimated_total' => round((float) ($planStats->estimated_total ?? 0), 2),
                'actual_total' => round((float) ($planStats->actual_total ?? 0), 2),
            ],
            'topPatients' => $topPatients,

            // Chart
            'chartStats' => $chartStats,

            // Treatment type breakdown
            'treatmentTypeBreakdown' => $treatmentTypeBreakdown,

            // Risk patients
            'riskPatientsCount' => $riskPatientsCount,

            // Follow-ups
            'followupStats' => [
                'total' => (int) ($followupStats->total ?? 0),
                'pending' => (int) ($followupStats->pending ?? 0),
                'overdue' => (int) ($followupStats->overdue ?? 0),
                'booked' => (int) ($followupStats->booked ?? 0),
                'completed' => (int) ($followupStats->completed ?? 0),
                'cancelled' => (int) ($followupStats->cancelled ?? 0),
                'booking_rate' => $followupBookingRate,
            ],

            // Doctor productivity
            'doctorProductivity' => $doctorProductivity,

            // Patient retention
            'patientRetention' => $patientRetention,
        ]);
    }

    /**
     * Download dental chart PDF for a patient.
     */
    public function chartPdf(Patient $patient)
    {
        $chart = DentalChart::where('patient_id', $patient->id)
            ->orderBy('tooth_number')
            ->get()
            ->keyBy('tooth_number');

        $treatments = DentalTreatment::where('patient_id', $patient->id)
            ->with('doctor:id,name_en,name_ar')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $pdf = Pdf::loadView('pdf.dental-chart', [
            'patient' => $patient,
            'chart' => $chart,
            'treatments' => $treatments,
            'conditions' => DentalChart::CONDITIONS,
            'allTeeth' => DentalChart::ALL_TEETH,
            'upperRight' => DentalChart::UPPER_RIGHT,
            'upperLeft' => DentalChart::UPPER_LEFT,
            'lowerLeft' => DentalChart::LOWER_LEFT,
            'lowerRight' => DentalChart::LOWER_RIGHT,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('dental-chart-' . $patient->file_number . '-' . now()->format('Y-m-d') . '.pdf');
    }
}

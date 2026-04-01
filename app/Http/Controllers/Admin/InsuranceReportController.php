<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Models\PatientInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InsuranceReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // Monthly Claims Overview
        $claimStats = InsuranceClaim::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw("
                COUNT(*) as total_claims,
                SUM(CASE WHEN status IN ('paid','partially_paid') THEN 1 ELSE 0 END) as paid_claims,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_claims,
                SUM(CASE WHEN status IN ('draft','submitted','under_review') THEN 1 ELSE 0 END) as pending_claims,
                SUM(claimed_amount) as total_claimed,
                SUM(approved_amount) as total_approved,
                SUM(paid_amount) as total_paid,
                ROUND(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 1) as rejection_rate
            ")
            ->first();

        // Collection Rate
        $collectionRate = $claimStats->total_claimed > 0
            ? round(($claimStats->total_paid / $claimStats->total_claimed) * 100, 1)
            : 0;

        // Claims by Company
        $companyPerformance = InsuranceClaim::whereBetween('insurance_claims.created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->join('insurance_companies', 'insurance_claims.insurance_company_id', '=', 'insurance_companies.id')
            ->select(
                'insurance_companies.id',
                'insurance_companies.name_ar',
                'insurance_companies.name_en',
                DB::raw('COUNT(*) as total_claims'),
                DB::raw("SUM(CASE WHEN insurance_claims.status IN ('paid','partially_paid') THEN 1 ELSE 0 END) as paid_claims"),
                DB::raw("SUM(CASE WHEN insurance_claims.status = 'rejected' THEN 1 ELSE 0 END) as rejected_claims"),
                DB::raw('SUM(insurance_claims.claimed_amount) as total_claimed'),
                DB::raw('SUM(insurance_claims.approved_amount) as total_approved'),
                DB::raw('SUM(insurance_claims.paid_amount) as total_paid'),
                DB::raw("ROUND(SUM(CASE WHEN insurance_claims.status = 'rejected' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 1) as rejection_rate"),
                DB::raw('ROUND(SUM(insurance_claims.paid_amount) * 100.0 / NULLIF(SUM(insurance_claims.claimed_amount), 0), 1) as collection_rate')
            )
            ->groupBy('insurance_companies.id', 'insurance_companies.name_ar', 'insurance_companies.name_en')
            ->orderByDesc('total_paid')
            ->get();

        // Monthly Trend (last 6 months)
        $monthlyTrend = InsuranceClaim::where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as claims'),
                DB::raw('SUM(claimed_amount) as claimed'),
                DB::raw('SUM(paid_amount) as paid'),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Active Insured Patients
        $activeInsured = PatientInsurance::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->count();

        // Average Processing Time (submitted to paid)
        $avgProcessingDays = InsuranceClaim::whereIn('status', ['paid', 'partially_paid'])
            ->whereNotNull('submitted_date')
            ->whereNotNull('paid_date')
            ->selectRaw('ROUND(AVG(DATEDIFF(paid_date, submitted_date)), 0) as avg_days')
            ->value('avg_days');

        // Aging Analysis
        $aging = InsuranceClaim::whereIn('status', ['submitted', 'under_review', 'approved'])
            ->select(
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), submitted_date) <= 30 THEN 1 ELSE 0 END) as within_30"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), submitted_date) BETWEEN 31 AND 60 THEN 1 ELSE 0 END) as days_31_60"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), submitted_date) BETWEEN 61 AND 90 THEN 1 ELSE 0 END) as days_61_90"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), submitted_date) > 90 THEN 1 ELSE 0 END) as over_90")
            )
            ->first();

        return Inertia::render('Admin/Insurance/Reports', [
            'claimStats' => $claimStats,
            'collectionRate' => $collectionRate,
            'companyPerformance' => $companyPerformance,
            'monthlyTrend' => $monthlyTrend,
            'activeInsured' => $activeInsured,
            'avgProcessingDays' => $avgProcessingDays ?? 0,
            'aging' => $aging,
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }
}

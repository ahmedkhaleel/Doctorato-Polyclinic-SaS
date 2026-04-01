<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\DentalLabOrder;
use App\Models\DentalScheduledFollowup;
use App\Models\Doctor;
use App\Models\Visit;
use App\Models\Patient;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DentalDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth();

        // Today's dental appointments (always fresh — changes throughout the day)
        $todayAppointments = Visit::where('module', 'dental')
            ->where('visit_date', $today)
            ->with(['patient:id,full_name,file_number,phone', 'doctor:id,name_ar,name_en', 'service:id,name_ar,name_en'])
            ->orderBy('scheduled_time')
            ->get();

        // Pending treatment plans
        $pendingPlans = DentalTreatmentPlan::whereIn('status', ['approved', 'in_progress'])
            ->with(['patient:id,full_name,file_number', 'doctor:id,name_ar,name_en'])
            ->latest()
            ->limit(10)
            ->get();

        // Pending lab orders
        $pendingLabOrders = DentalLabOrder::pending()
            ->with(['patient:id,full_name', 'doctor:id,name_ar,name_en'])
            ->orderBy('expected_date')
            ->limit(10)
            ->get();

        // Overdue lab orders
        $overdueLabOrders = DentalLabOrder::overdue()->count();

        // ── Cached monthly stats (refreshed every 10 minutes) ──────────
        $monthlyCacheKey = 'dental_dashboard_monthly:' . $thisMonth->format('Y-m');

        $monthlyStats = Cache::remember($monthlyCacheKey, 600, function () use ($thisMonth) {
            $monthlyTreatments = DentalTreatment::where('created_at', '>=', $thisMonth)->count();
            $monthlyCompleted = DentalTreatment::where('status', 'completed')
                ->where('completed_at', '>=', $thisMonth)->count();

            // Fixed N+1: Use JOIN instead of loading all visits + invoices in PHP
            $monthlyRevenue = (float) Visit::where('visits.module', 'dental')
                ->where('visits.visit_date', '>=', $thisMonth)
                ->join('invoices', 'visits.id', '=', 'invoices.visit_id')
                ->sum('invoices.total');

            $monthlyVisits = Visit::where('module', 'dental')
                ->where('visit_date', '>=', $thisMonth)->count();

            $uniquePatients = Visit::where('module', 'dental')
                ->where('visit_date', '>=', $thisMonth)
                ->distinct('patient_id')
                ->count('patient_id');

            return [
                'monthly_visits' => $monthlyVisits,
                'monthly_revenue' => round($monthlyRevenue, 2),
                'treatment_completion_rate' => $monthlyTreatments > 0
                    ? round(($monthlyCompleted / $monthlyTreatments) * 100)
                    : 0,
                'dental_patients_this_month' => $uniquePatients,
            ];
        });

        // ── Cached slow counts (refreshed every 15 minutes) ──────────
        $slowCounts = Cache::remember('dental_dashboard_slow_counts', 900, function () {
            return [
                'risk_patients' => Patient::where(function ($q) {
                    $q->where('latex_allergy', true)
                      ->orWhere('anesthesia_complications', true)
                      ->orWhere('has_bleeding_disorder', true)
                      ->orWhere('takes_blood_thinners', true)
                      ->orWhere('has_heart_condition', true)
                      ->orWhere('has_hepatitis', true)
                      ->orWhere('has_hiv', true)
                      ->orWhere('is_pregnant', true);
                })->whereHas('visits', fn ($q) => $q->where('module', 'dental'))->count(),
                'dental_doctors' => Doctor::where('module', 'dental')->active()->count(),
            ];
        });

        // Statistics (merged: real-time + cached)
        $stats = array_merge($monthlyStats, $slowCounts, [
            'today_appointments' => $todayAppointments->count(),
            'today_completed' => $todayAppointments->where('status', 'completed')->count(),
            'today_waiting' => $todayAppointments->where('status', 'waiting')->count(),
            'active_treatment_plans' => DentalTreatmentPlan::active()->count(),
            'pending_lab_orders' => DentalLabOrder::pending()->count(),
            'overdue_lab_orders' => $overdueLabOrders,
        ]);

        // Module settings for display
        $moduleSettings = ModuleManager::getSettings('dental');

        // ── Medical Risk Alerts for today's patients ──────────────
        $medicalAlerts = [];
        foreach ($todayAppointments as $apt) {
            if ($apt->patient) {
                $riskFlags = $apt->patient->getDentalRiskFlags();
                if (!empty($riskFlags)) {
                    $medicalAlerts[$apt->id] = $riskFlags;
                }
            }
        }

        // ── Pending Followups ────────────────────────────────────
        $pendingFollowups = DentalScheduledFollowup::with([
                'patient:id,full_name,file_number,phone',
                'doctor:id,name_ar,name_en',
                'treatment:id,treatment_type,tooth_number',
            ])
            ->needsBooking()
            ->orderBy('scheduled_date')
            ->limit(8)
            ->get();

        $overdueFollowups = DentalScheduledFollowup::where('status', DentalScheduledFollowup::STATUS_PENDING)
            ->whereNull('booking_id')
            ->where('scheduled_date', '<', $today)
            ->count();

        // ── Critical Alerts Summary ──────────────────────────────
        $criticalAlerts = [];

        // Overdue lab orders
        if ($overdueLabOrders > 0) {
            $criticalAlerts[] = [
                'type' => 'lab_overdue',
                'severity' => 'high',
                'count' => $overdueLabOrders,
                'message_ar' => "يوجد {$overdueLabOrders} طلب معمل متأخر عن الموعد",
                'message_en' => "{$overdueLabOrders} lab order(s) past expected delivery date",
                'action_url' => '/admin/dental/lab-orders?status=overdue',
            ];
        }

        // Overdue followups
        if ($overdueFollowups > 0) {
            $criticalAlerts[] = [
                'type' => 'followup_overdue',
                'severity' => 'high',
                'count' => $overdueFollowups,
                'message_ar' => "يوجد {$overdueFollowups} متابعة متأخرة بدون حجز",
                'message_en' => "{$overdueFollowups} follow-up(s) overdue without booking",
                'action_url' => '/admin/dental/followup-rules',
            ];
        }

        // High-risk patients today
        $highRiskTodayCount = count($medicalAlerts);
        if ($highRiskTodayCount > 0) {
            $criticalAlerts[] = [
                'type' => 'risk_patients_today',
                'severity' => 'medium',
                'count' => $highRiskTodayCount,
                'message_ar' => "يوجد {$highRiskTodayCount} مريض عالي الخطورة في مواعيد اليوم",
                'message_en' => "{$highRiskTodayCount} high-risk patient(s) in today's appointments",
                'action_url' => null,
            ];
        }

        // Stalled treatment plans (in_progress but no activity in 14+ days)
        $stalledPlans = DentalTreatmentPlan::where('status', 'in_progress')
            ->where('updated_at', '<', now()->subDays(14))
            ->count();
        if ($stalledPlans > 0) {
            $criticalAlerts[] = [
                'type' => 'stalled_plans',
                'severity' => 'medium',
                'count' => $stalledPlans,
                'message_ar' => "يوجد {$stalledPlans} خطة علاج متوقفة منذ أكثر من 14 يوم",
                'message_en' => "{$stalledPlans} treatment plan(s) stalled for 14+ days",
                'action_url' => '/admin/dental/treatment-plans?status=in_progress',
            ];
        }

        // ── Doctor Performance (this month, cached) ─────────────
        $doctorPerformance = Cache::remember('dental_dashboard_doctor_perf:' . $thisMonth->format('Y-m'), 600, function () use ($thisMonth) {
            return DentalTreatment::where('dental_treatments.created_at', '>=', $thisMonth)
                ->join('doctors', 'dental_treatments.doctor_id', '=', 'doctors.id')
                ->select(
                    'doctors.id',
                    'doctors.name_en',
                    'doctors.name_ar',
                    'doctors.photo',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN dental_treatments.status = 'completed' THEN 1 ELSE 0 END) as completed"),
                    DB::raw('SUM(dental_treatments.cost) as revenue')
                )
                ->groupBy('doctors.id', 'doctors.name_en', 'doctors.name_ar', 'doctors.photo')
                ->orderByDesc('completed')
                ->limit(5)
                ->get();
        });

        // Add followup stats to main stats
        $stats['pending_followups'] = DentalScheduledFollowup::where('status', DentalScheduledFollowup::STATUS_PENDING)
            ->whereNull('booking_id')
            ->count();
        $stats['overdue_followups'] = $overdueFollowups;
        $stats['dental_patients_this_month'] = $monthlyStats['dental_patients_this_month'] ?? 0;

        return Inertia::render('Admin/Dental/Dashboard', [
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'pendingPlans' => $pendingPlans,
            'pendingLabOrders' => $pendingLabOrders,
            'moduleSettings' => $moduleSettings,
            'medicalAlerts' => $medicalAlerts,
            'pendingFollowups' => $pendingFollowups,
            'overdueFollowups' => $overdueFollowups,
            'criticalAlerts' => $criticalAlerts,
            'doctorPerformance' => $doctorPerformance,
        ]);
    }
}

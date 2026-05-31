<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\DentalLabOrder;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\PediatricGrowthRecord;
use App\Models\PediatricScreeningTest;
use App\Models\PediatricVaccination;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Leave;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Supply;
use App\Models\Visit;
use App\Services\ModuleManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();
        $enabledModules = array_keys(ModuleManager::getActiveModules());

        // ─── Cached: Financial KPIs (refreshed every 15 min) ────
        $financial = Cache::remember('admin-dashboard:financial:' . $now->format('Y-m'), 900, function () use ($now) {
            $startOfMonth = $now->copy()->startOfMonth();
            $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
            $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

            $revenueThisMonth = Payment::whereMonth('payment_date', $now->month)
                ->whereYear('payment_date', $now->year)
                ->sum('amount');

            $revenueLastMonth = Payment::whereBetween('payment_date', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            $expensesThisMonth = Expense::whereMonth('expense_date', $now->month)
                ->whereYear('expense_date', $now->year)
                ->sum('amount');

            $unpaidBalance = Invoice::whereIn('status', ['unpaid', 'partial'])
                ->selectRaw('SUM(total - paid_amount) as balance')
                ->value('balance') ?? 0;

            $revenueByModule = app(\App\Services\Branch\BranchContext::class)
                ->applyToBuilder(DB::table('payments'), 'payments.branch_id')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->whereMonth('payments.payment_date', $now->month)
                ->whereYear('payments.payment_date', $now->year)
                ->select('invoices.module', DB::raw('SUM(payments.amount) as total'))
                ->groupBy('invoices.module')
                ->pluck('total', 'module')
                ->toArray();

            return [
                'revenue_this_month' => round((float) $revenueThisMonth, 2),
                'revenue_last_month' => round((float) $revenueLastMonth, 2),
                'expenses_this_month' => round((float) $expensesThisMonth, 2),
                'net_income' => round((float) $revenueThisMonth - (float) $expensesThisMonth, 2),
                'unpaid_balance' => round((float) $unpaidBalance, 2),
                'revenue_by_module' => collect($revenueByModule)->mapWithKeys(fn ($v, $k) => [$k ?: 'unassigned' => round((float) $v, 2)])->toArray(),
            ];
        });

        // ─── Clinic KPIs (real-time — not cached) ───────────────
        $todayVisits = Visit::whereDate('visit_date', today())->whereIn('module', $enabledModules);
        $clinic = [
            'today_visits' => $todayVisits->count(),
            'today_waiting' => (clone $todayVisits)->where('status', 'waiting')->count(),
            'today_completed' => (clone $todayVisits)->where('status', 'completed')->count(),
            'month_visits' => Visit::whereMonth('visit_date', $now->month)
                ->whereYear('visit_date', $now->year)->whereIn('module', $enabledModules)->count(),
            'new_patients_month' => Patient::whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)->count(),
            'total_patients' => Patient::count(),
        ];

        // ─── Alerts (real-time — not cached) ─────────────────────
        $alerts = [
            'pending_bookings' => Booking::where('status', 'new')->whereIn('module', $enabledModules)->count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
            'low_stock_count' => ModuleManager::isEnabled('inventory') ? Supply::whereColumn('quantity', '<=', 'min_quantity')->count() : 0,
            'pending_leaves' => ModuleManager::isEnabled('hr') ? Leave::where('status', 'pending')->count() : 0,
        ];

        // ─── Cached: Revenue Trend (refreshed every 30 min) ─────
        $revenueTrendData = Cache::remember('admin-dashboard:revenue-trend:' . today()->format('Y-m-d'), 1800, function () use ($now) {
            $revenueTrend = Payment::select(
                    DB::raw('DATE(payment_date) as date'),
                    DB::raw('SUM(amount) as total')
                )
                ->where('payment_date', '>=', $now->copy()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->format('Y-m-d');
                $data[] = [
                    'label' => Carbon::parse($date)->format('D'),
                    'value' => round((float) ($revenueTrend[$date]->total ?? 0), 2),
                ];
            }
            return $data;
        });

        // ─── Cached: Visit Trend (refreshed every 30 min) ───────
        $visitTrendData = Cache::remember('admin-dashboard:visit-trend:' . today()->format('Y-m-d'), 1800, function () use ($now, $enabledModules) {
            $visitTrend = Visit::select(
                    DB::raw('DATE(visit_date) as date'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('visit_date', '>=', $now->copy()->subDays(6)->startOfDay())
                ->whereIn('module', $enabledModules)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->format('Y-m-d');
                $data[] = [
                    'label' => Carbon::parse($date)->format('D'),
                    'value' => (int) ($visitTrend[$date]->total ?? 0),
                ];
            }
            return $data;
        });

        // ─── Today Queue (real-time) ────────────────────────────
        $todayQueue = Visit::with(['patient:id,full_name,file_number', 'doctor:id,name_en', 'service:id,name_en'])
            ->whereDate('visit_date', today())
            ->whereIn('status', ['waiting', 'in_progress'])
            ->whereIn('module', $enabledModules)
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        // ─── Unpaid Invoices (real-time) ─────────────────────────
        $unpaidInvoices = Invoice::with('patient:id,full_name,phone')
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderByDesc(DB::raw('total - paid_amount'))
            ->limit(5)
            ->get();

        // ─── Cached: Top Services (refreshed every 30 min) ──────
        $topServices = Cache::remember('admin-dashboard:top-services:' . $now->format('Y-m'), 1800, function () use ($now, $enabledModules) {
            return Visit::select('service_id', DB::raw('COUNT(*) as visit_count'))
                ->with('service:id,name_en')
                ->whereMonth('visit_date', $now->month)
                ->whereYear('visit_date', $now->year)
                ->where('status', 'completed')
                ->whereNotNull('service_id')
                ->whereIn('module', $enabledModules)
                ->groupBy('service_id')
                ->orderByDesc('visit_count')
                ->limit(5)
                ->get();
        });

        // ─── Recent Bookings (real-time) ─────────────────────────
        $recentBookings = Booking::with(['service', 'doctor'])
            ->whereIn('module', $enabledModules)
            ->latest()
            ->limit(5)
            ->get();

        // ─── CRM Summary ───────────────────────────────────────
        $crm = [
            'new_leads' => Lead::where('status', 'new')->count(),
            'hot_leads' => Lead::where('priority', Lead::PRIORITY_HOT)->whereNotIn('status', ['converted', 'lost'])->count(),
            'month_leads' => Lead::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'month_conversions' => Lead::where('status', 'converted')->whereMonth('converted_at', $now->month)->whereYear('converted_at', $now->year)->count(),
            'overdue_follow_ups' => LeadFollowUp::where('status', 'pending')->where('scheduled_at', '<', now())->count(),
            'today_follow_ups' => LeadFollowUp::where('status', 'pending')->whereDate('scheduled_at', today())->count(),
        ];

        // ─── Dental Summary (only if module enabled) ────────────
        $dental = null;
        if (ModuleManager::isEnabled('dental')) {
            $dental = Cache::remember('admin-dashboard:dental:' . today()->format('Y-m-d'), 900, function () use ($now) {
                $dentalRevenueMonth = DentalTreatment::where('status', 'completed')
                    ->whereMonth('completed_at', $now->month)
                    ->whereYear('completed_at', $now->year)
                    ->sum(DB::raw('cost + lab_cost'));

                return [
                    'revenue_this_month' => round((float) $dentalRevenueMonth, 2),
                    'treatments_today' => DentalTreatment::whereDate('created_at', today())->count(),
                    'completed_today' => DentalTreatment::where('status', 'completed')->whereDate('completed_at', today())->count(),
                    'pending_lab_orders' => DentalLabOrder::pending()->count(),
                    'overdue_lab_orders' => DentalLabOrder::overdue()->count(),
                    'active_plans' => DentalTreatmentPlan::active()->count(),
                    'overdue_plans' => DentalTreatmentPlan::whereNotNull('expected_end_date')
                        ->where('expected_end_date', '<', now())
                        ->whereIn('status', ['approved', 'in_progress'])
                        ->count(),
                    'lab_ready' => DentalLabOrder::where('status', 'ready')->count(),
                ];
            });
        }

        // ─── Pediatric Summary (only if module enabled) ───────────
        $pediatric = null;
        if (ModuleManager::isEnabled('pediatric')) {
            $pediatric = Cache::remember('admin-dashboard:pediatric:' . today()->format('Y-m-d'), 900, function () use ($now) {
                $pediatricRevenueMonth = Invoice::whereHas('visit', function ($q) use ($now) {
                    $q->where('module', 'pediatric')
                        ->where('status', 'completed')
                        ->whereMonth('visit_date', $now->month)
                        ->whereYear('visit_date', $now->year);
                })->sum('total');

                return [
                    'total_patients' => Patient::whereHas('visits', fn ($q) => $q->where('module', 'pediatric'))->count(),
                    'visits_this_month' => Visit::where('module', 'pediatric')
                        ->whereMonth('visit_date', $now->month)
                        ->whereYear('visit_date', $now->year)
                        ->count(),
                    'vaccinations_due' => PediatricVaccination::where('status', PediatricVaccination::STATUS_SCHEDULED)
                        ->where('scheduled_date', '<=', today())
                        ->count(),
                    'growth_alerts' => PediatricGrowthRecord::where(function ($q) {
                        $q->where('weight_percentile', '<', 3)
                            ->orWhere('weight_percentile', '>', 97)
                            ->orWhere('height_percentile', '<', 3)
                            ->orWhere('bmi_percentile', '>', 97);
                    })->whereMonth('measurement_date', $now->month)->count(),
                    'revenue_this_month' => round((float) $pediatricRevenueMonth, 2),
                    'screening_pending' => PediatricScreeningTest::where('result', 'pending')->count(),
                ];
            });
        }

        // Engagement metrics — loyalty + referrals at a glance
        $engagement = [
            'loyalty'  => \App\Services\LoyaltyService::clinicStats(),
            'referral' => \App\Services\PatientReferralService::clinicStats(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'financial' => $financial,
            'clinic' => $clinic,
            'alerts' => $alerts,
            'crm' => $crm,
            'revenueTrend' => $revenueTrendData,
            'visitTrend' => $visitTrendData,
            'todayQueue' => $todayQueue,
            'unpaidInvoices' => $unpaidInvoices,
            'topServices' => $topServices,
            'recentBookings' => $recentBookings,
            'dental' => $dental,
            'pediatric' => $pediatric,
            'engagement' => $engagement,
        ]);
    }
}

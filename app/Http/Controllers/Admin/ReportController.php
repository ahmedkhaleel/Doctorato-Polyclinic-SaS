<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Visit;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $now = now();
        $lastMonth = $now->copy()->subMonth();
        $module = $request->input('module');

        // Helper: apply module filter via visits join for payments
        $paymentModuleFilter = function ($query) use ($module) {
            if ($module) {
                $query->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                    ->join('visits', 'invoices.visit_id', '=', 'visits.id')
                    ->where('visits.module', $module);
            }
        };

        // Current month
        $revenueQuery = Payment::whereMonth('payment_date', $now->month)->whereYear('payment_date', $now->year);
        if ($module) {
            $revenueQuery->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $module)));
        }
        $revenue = round((float) $revenueQuery->sum('amount'), 2);

        $expenses = round((float) Expense::whereMonth('expense_date', $now->month)
            ->whereYear('expense_date', $now->year)->sum('amount'), 2);

        $visitsQuery = Visit::whereMonth('visit_date', $now->month)->whereYear('visit_date', $now->year);
        if ($module) { $visitsQuery->where('module', $module); }
        $visits = $visitsQuery->count();

        $newPatients = Patient::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();

        $completedQuery = Visit::where('status', 'completed')
            ->whereMonth('visit_date', $now->month)->whereYear('visit_date', $now->year);
        if ($module) { $completedQuery->where('module', $module); }
        $completedVisits = $completedQuery->count();

        // Last month for comparison
        $prevRevenueQuery = Payment::whereMonth('payment_date', $lastMonth->month)->whereYear('payment_date', $lastMonth->year);
        if ($module) {
            $prevRevenueQuery->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $module)));
        }
        $prevRevenue = round((float) $prevRevenueQuery->sum('amount'), 2);

        $prevExpenses = round((float) Expense::whereMonth('expense_date', $lastMonth->month)
            ->whereYear('expense_date', $lastMonth->year)->sum('amount'), 2);

        $prevVisitsQuery = Visit::whereMonth('visit_date', $lastMonth->month)->whereYear('visit_date', $lastMonth->year);
        if ($module) { $prevVisitsQuery->where('module', $module); }
        $prevVisits = $prevVisitsQuery->count();

        $prevPatients = Patient::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)->count();

        // Daily revenue for mini sparkline (last 14 days)
        $dailyRevenueQuery = Payment::whereDate('payment_date', '>=', $now->copy()->subDays(13)->toDateString())
            ->whereDate('payment_date', '<=', $now->toDateString());
        if ($module) {
            $dailyRevenueQuery->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $module)));
        }
        $dailyRevenue = $dailyRevenueQuery
            ->select(DB::raw('DATE(payment_date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Top 5 services this month
        $topServicesQuery = Service::active();
        if ($module) { $topServicesQuery->where('module', $module); }
        $topServices = $topServicesQuery
            ->withCount([
                'visits' => fn ($q) => $q->where('status', 'completed')
                    ->whereMonth('visit_date', $now->month)
                    ->whereYear('visit_date', $now->year)
                    ->when($module, fn ($q2) => $q2->where('module', $module)),
            ])
            ->having('visits_count', '>', 0)
            ->orderByDesc('visits_count')
            ->limit(5)
            ->get(['id', 'name_en', 'name_ar']);

        // Top 5 doctors this month by visits
        $topDoctorsQuery = Doctor::active();
        if ($module) { $topDoctorsQuery->where('module', $module); }
        $topDoctors = $topDoctorsQuery
            ->withCount([
                'visits' => fn ($q) => $q->where('status', 'completed')
                    ->whereMonth('visit_date', $now->month)
                    ->whereYear('visit_date', $now->year)
                    ->when($module, fn ($q2) => $q2->where('module', $module)),
            ])
            ->having('visits_count', '>', 0)
            ->orderByDesc('visits_count')
            ->limit(5)
            ->get(['id', 'name_en', 'name_ar', 'photo']);

        $unpaidQuery = Invoice::whereIn('status', ['unpaid', 'partial']);
        if ($module) {
            $unpaidQuery->whereHas('visit', fn ($q) => $q->where('module', $module));
        }

        $summary = [
            'revenue'         => $revenue,
            'expenses'        => $expenses,
            'net_income'      => round($revenue - $expenses, 2),
            'visits'          => $visits,
            'completed_visits'=> $completedVisits,
            'new_patients'    => $newPatients,
            'unpaid_count'    => (clone $unpaidQuery)->count(),
            'unpaid_amount'   => round((float) (clone $unpaidQuery)
                ->selectRaw('SUM(total - paid_amount) as due')->value('due'), 2),
            'total_patients'  => Patient::count(),
            'prev_revenue'    => $prevRevenue,
            'prev_expenses'   => $prevExpenses,
            'prev_visits'     => $prevVisits,
            'prev_patients'   => $prevPatients,
        ];

        // ── Module Comparison (derma vs dental vs pediatric) ────────────
        $moduleComparison = null;
        $modulesConfig = ModuleManager::getForFrontend();
        $enabledMedical = collect(['derma', 'dental', 'pediatric'])
            ->filter(fn ($m) => $modulesConfig[$m]['enabled'] ?? false);
        if ($enabledMedical->count() >= 2 && !$module) {
            $moduleComparison = [];
            foreach ($enabledMedical as $mod) {
                $modRevenueQ = Payment::whereMonth('payment_date', $now->month)->whereYear('payment_date', $now->year)
                    ->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $mod)));
                $modRevenue = round((float) $modRevenueQ->sum('amount'), 2);

                $modPrevRevenueQ = Payment::whereMonth('payment_date', $lastMonth->month)->whereYear('payment_date', $lastMonth->year)
                    ->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $mod)));
                $modPrevRevenue = round((float) $modPrevRevenueQ->sum('amount'), 2);

                $modVisits = Visit::whereMonth('visit_date', $now->month)->whereYear('visit_date', $now->year)
                    ->where('module', $mod)->count();
                $modPrevVisits = Visit::whereMonth('visit_date', $lastMonth->month)->whereYear('visit_date', $lastMonth->year)
                    ->where('module', $mod)->count();

                $modCompleted = Visit::where('status', 'completed')
                    ->whereMonth('visit_date', $now->month)->whereYear('visit_date', $now->year)
                    ->where('module', $mod)->count();

                $modNewPatients = Visit::whereMonth('visit_date', $now->month)->whereYear('visit_date', $now->year)
                    ->where('module', $mod)
                    ->distinct('patient_id')->count('patient_id');

                $moduleComparison[$mod] = [
                    'revenue' => $modRevenue,
                    'prev_revenue' => $modPrevRevenue,
                    'visits' => $modVisits,
                    'prev_visits' => $modPrevVisits,
                    'completed' => $modCompleted,
                    'patients' => $modNewPatients,
                ];
            }
        }

        return Inertia::render('Admin/Reports/Index', [
            'summary'          => $summary,
            'dailyRevenue'     => $dailyRevenue,
            'topServices'      => $topServices,
            'topDoctors'       => $topDoctors,
            'monthLabel'       => $now->format('F Y'),
            'modules'          => $modulesConfig,
            'moduleComparison' => $moduleComparison,
            'filters'          => $request->only(['module']),
        ]);
    }

    public function financial(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $module = $request->input('module');

        $revenueQuery = Payment::whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);
        if ($module) {
            $revenueQuery->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $module)));
        }
        $totalRevenue = $revenueQuery->sum('amount');

        $totalExpenses = Expense::whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->sum('amount');

        $unpaidQuery = Invoice::where('status', '!=', 'paid')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
        if ($module) {
            $unpaidQuery->whereHas('visit', fn ($q) => $q->where('module', $module));
        }
        $unpaidInvoices = $unpaidQuery->sum(DB::raw('total - paid_amount'));

        $revenueByMethodQuery = Payment::whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);
        if ($module) {
            $revenueByMethodQuery->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $module)));
        }
        $revenueByMethod = $revenueByMethodQuery
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'payment_methods.name_en as label',
                DB::raw('SUM(payments.amount) as value'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name_en')
            ->get();

        $expensesByCategory = Expense::whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name_en as label',
                DB::raw('SUM(expenses.amount) as value'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('expense_categories.id', 'expense_categories.name_en')
            ->get();

        $dailyRevenueQuery = Payment::whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);
        if ($module) {
            $dailyRevenueQuery->whereHas('invoice', fn ($q) => $q->whereHas('visit', fn ($q2) => $q2->where('module', $module)));
        }
        $dailyRevenue = $dailyRevenueQuery
            ->select(DB::raw('DATE(payment_date) as label'), DB::raw('SUM(amount) as value'))
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('label')
            ->get();

        return Inertia::render('Admin/Reports/Financial', [
            'totalRevenue' => round((float) $totalRevenue, 2),
            'totalExpenses' => round((float) $totalExpenses, 2),
            'netIncome' => round((float) $totalRevenue - (float) $totalExpenses, 2),
            'unpaidInvoices' => round((float) $unpaidInvoices, 2),
            'revenueByMethod' => $revenueByMethod,
            'expensesByCategory' => $expensesByCategory,
            'dailyRevenue' => $dailyRevenue,
            'modules' => ModuleManager::getForFrontend(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'module' => $module,
            ],
        ]);
    }

    public function doctors(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $module = $request->input('module');

        $doctorsQuery = Doctor::active();
        if ($module) { $doctorsQuery->where('module', $module); }

        $doctors = $doctorsQuery
            ->withCount([
                'visits' => fn ($q) => $q->whereDate('visit_date', '>=', $dateFrom)
                    ->whereDate('visit_date', '<=', $dateTo)
                    ->when($module, fn ($q2) => $q2->where('module', $module)),
                'visits as completed_visits_count' => fn ($q) => $q->where('status', 'completed')
                    ->whereDate('visit_date', '>=', $dateFrom)
                    ->whereDate('visit_date', '<=', $dateTo)
                    ->when($module, fn ($q2) => $q2->where('module', $module)),
                'visits as consultation_count' => fn ($q) => $q->where('visit_type', 'consultation')
                    ->whereDate('visit_date', '>=', $dateFrom)
                    ->whereDate('visit_date', '<=', $dateTo)
                    ->when($module, fn ($q2) => $q2->where('module', $module)),
                'visits as session_count' => fn ($q) => $q->where('visit_type', 'session')
                    ->whereDate('visit_date', '>=', $dateFrom)
                    ->whereDate('visit_date', '<=', $dateTo)
                    ->when($module, fn ($q2) => $q2->where('module', $module)),
            ])
            ->get();

        // Revenue per doctor (optimized)
        $revenueQuery = Invoice::join('visits', 'invoices.visit_id', '=', 'visits.id')
            ->whereDate('visits.visit_date', '>=', $dateFrom)
            ->whereDate('visits.visit_date', '<=', $dateTo)
            ->where('visits.status', 'completed');
        if ($module) { $revenueQuery->where('visits.module', $module); }
        $doctorRevenue = $revenueQuery
            ->select('visits.doctor_id', DB::raw('SUM(invoices.total) as total'))
            ->groupBy('visits.doctor_id')
            ->pluck('total', 'doctor_id');

        // Commission per doctor
        $commissionQuery = Visit::where('status', 'completed')
            ->whereDate('visit_date', '>=', $dateFrom)
            ->whereDate('visit_date', '<=', $dateTo)
            ->whereNotNull('commission_amount');
        if ($module) { $commissionQuery->where('module', $module); }
        $doctorCommission = $commissionQuery
            ->select('doctor_id', DB::raw('SUM(commission_amount) as total'))
            ->groupBy('doctor_id')
            ->pluck('total', 'doctor_id');

        return Inertia::render('Admin/Reports/Doctors', [
            'doctors' => $doctors,
            'doctorRevenue' => $doctorRevenue,
            'doctorCommission' => $doctorCommission,
            'modules' => ModuleManager::getForFrontend(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'module' => $module,
            ],
        ]);
    }

    public function patients(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $module = $request->input('module');

        $newPatients = Patient::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();

        $totalPatients = Patient::count();

        $patientsByReferral = Patient::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select('referral_source', DB::raw('COUNT(*) as count'))
            ->groupBy('referral_source')
            ->get();

        $genderDistribution = Patient::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select('gender', DB::raw('COUNT(*) as count'))
            ->groupBy('gender')
            ->get();

        $topPatients = Patient::withCount([
            'visits' => fn ($q) => $q->whereDate('visit_date', '>=', $dateFrom)
                ->whereDate('visit_date', '<=', $dateTo)
                ->when($module, fn ($q2) => $q2->where('module', $module)),
        ])
            ->having('visits_count', '>', 0)
            ->orderByDesc('visits_count')
            ->limit(10)
            ->get();

        $topSpendersQuery = Patient::select('patients.*')
            ->join('invoices', 'patients.id', '=', 'invoices.patient_id')
            ->whereDate('invoices.created_at', '>=', $dateFrom)
            ->whereDate('invoices.created_at', '<=', $dateTo);
        if ($module) {
            $topSpendersQuery->join('visits', 'invoices.visit_id', '=', 'visits.id')
                ->where('visits.module', $module);
        }
        $topSpenders = $topSpendersQuery
            ->groupBy('patients.id')
            ->selectRaw('SUM(invoices.paid_amount) as total_spent')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Reports/Patients', [
            'newPatients' => $newPatients,
            'totalPatients' => $totalPatients,
            'patientsByReferral' => $patientsByReferral,
            'genderDistribution' => $genderDistribution,
            'topPatients' => $topPatients,
            'topSpenders' => $topSpenders,
            'modules' => ModuleManager::getForFrontend(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'module' => $module,
            ],
        ]);
    }

    public function services(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $module = $request->input('module');

        $serviceUsageQuery = Service::active();
        if ($module) { $serviceUsageQuery->where('module', $module); }
        $serviceUsage = $serviceUsageQuery
            ->withCount([
                'visits' => fn ($q) => $q->whereDate('visit_date', '>=', $dateFrom)
                    ->whereDate('visit_date', '<=', $dateTo)
                    ->where('status', 'completed')
                    ->when($module, fn ($q2) => $q2->where('module', $module)),
            ])
            ->orderByDesc('visits_count')
            ->get();

        $serviceRevenueQuery = Invoice::join('visits', 'invoices.visit_id', '=', 'visits.id')
            ->join('services', 'visits.service_id', '=', 'services.id')
            ->whereDate('visits.visit_date', '>=', $dateFrom)
            ->whereDate('visits.visit_date', '<=', $dateTo)
            ->where('visits.status', 'completed');
        if ($module) { $serviceRevenueQuery->where('services.module', $module); }
        $serviceRevenue = $serviceRevenueQuery
            ->select(
                'services.id',
                'services.name_en',
                DB::raw('COUNT(*) as visit_count'),
                DB::raw('SUM(invoices.total) as total_revenue')
            )
            ->groupBy('services.id', 'services.name_en')
            ->orderByDesc('total_revenue')
            ->get();

        return Inertia::render('Admin/Reports/Services', [
            'serviceUsage' => $serviceUsage,
            'serviceRevenue' => $serviceRevenue,
            'modules' => ModuleManager::getForFrontend(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'module' => $module,
            ],
        ]);
    }
}

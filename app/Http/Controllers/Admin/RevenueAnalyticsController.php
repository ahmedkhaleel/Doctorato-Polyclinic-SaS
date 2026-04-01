<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RevenueAnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $now = now();
        $module = $request->input('module');

        // ── Current & Previous Month ──────────────────────────
        $curStart = $now->copy()->startOfMonth();
        $curEnd = $now->copy()->endOfMonth();
        $prevStart = $now->copy()->subMonth()->startOfMonth();
        $prevEnd = $now->copy()->subMonth()->endOfMonth();

        $paymentQuery = fn () => Payment::query()->when($module, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->where('module', $module)));
        $invoiceQuery = fn () => Invoice::query()->when($module, fn ($q) => $q->where('module', $module));

        $currentRevenue = (float) $paymentQuery()->whereBetween('payment_date', [$curStart, $curEnd])->sum('amount');
        $previousRevenue = (float) $paymentQuery()->whereBetween('payment_date', [$prevStart, $prevEnd])->sum('amount');
        $currentExpenses = (float) Expense::whereBetween('expense_date', [$curStart, $curEnd])->sum('amount');
        $previousExpenses = (float) Expense::whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount');
        $currentInvoiceCount = $invoiceQuery()->whereBetween('created_at', [$curStart, $curEnd])->count();
        $previousInvoiceCount = $invoiceQuery()->whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $avgTicket = $currentInvoiceCount > 0 ? $currentRevenue / $currentInvoiceCount : 0;
        $prevAvgTicket = $previousInvoiceCount > 0 ? $previousRevenue / $previousInvoiceCount : 0;

        $comparison = [
            'revenue' => ['current' => $currentRevenue, 'previous' => $previousRevenue],
            'expenses' => ['current' => $currentExpenses, 'previous' => $previousExpenses],
            'net_income' => [
                'current' => $currentRevenue - $currentExpenses,
                'previous' => $previousRevenue - $previousExpenses,
            ],
            'invoices' => ['current' => $currentInvoiceCount, 'previous' => $previousInvoiceCount],
            'avg_ticket' => ['current' => round($avgTicket, 2), 'previous' => round($prevAvgTicket, 2)],
        ];

        // ── Monthly Trend (12 months) ─────────────────────────
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $mStart = $month->copy()->startOfMonth();
            $mEnd = $month->copy()->endOfMonth();

            $rev = (float) $paymentQuery()->whereBetween('payment_date', [$mStart, $mEnd])->sum('amount');
            $exp = (float) Expense::whereBetween('expense_date', [$mStart, $mEnd])->sum('amount');

            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'revenue' => round($rev, 2),
                'expenses' => round($exp, 2),
                'net' => round($rev - $exp, 2),
            ];
        }

        // ── Top Services (current month) ──────────────────────
        $topServices = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [$curStart, $curEnd])
            ->when($module, fn ($q) => $q->where('invoices.module', $module))
            ->select(
                'invoice_items.description_en as name_en',
                'invoice_items.description_ar as name_ar',
                DB::raw('SUM(invoice_items.total) as total_revenue'),
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT invoices.id) as invoice_count')
            )
            ->groupBy('invoice_items.description_en', 'invoice_items.description_ar')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // ── Revenue by Doctor (current month) ─────────────────
        $doctorRevenue = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('visits', 'invoices.visit_id', '=', 'visits.id')
            ->join('doctors', 'visits.doctor_id', '=', 'doctors.id')
            ->whereBetween('payments.payment_date', [$curStart, $curEnd])
            ->when($module, fn ($q) => $q->where('invoices.module', $module))
            ->select(
                'doctors.name_en',
                'doctors.name_ar',
                DB::raw('SUM(payments.amount) as total_revenue'),
                DB::raw('COUNT(DISTINCT visits.id) as visit_count'),
                DB::raw('ROUND(SUM(payments.amount) / COUNT(DISTINCT visits.id), 2) as avg_per_visit')
            )
            ->groupBy('doctors.id', 'doctors.name_en', 'doctors.name_ar')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // ── Revenue by Day of Week (last 3 months) ────────────
        $dayOfWeek = DB::query()
            ->select(
                DB::raw('DAYOFWEEK(day) as dow'),
                DB::raw('ROUND(AVG(daily_total), 2) as avg_revenue')
            )
            ->fromSub(
                Payment::whereDate('payment_date', '>=', $now->copy()->subMonths(3))
                    ->select(DB::raw('DATE(payment_date) as day'), DB::raw('SUM(amount) as daily_total'))
                    ->groupBy(DB::raw('DATE(payment_date)')),
                'daily'
            )
            ->groupBy(DB::raw('DAYOFWEEK(day)'))
            ->orderBy('dow')
            ->pluck('avg_revenue', 'dow')
            ->toArray();

        $dayNames = [1 => 'Sun', 2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'];
        $dayNameAr = [1 => 'الأحد', 2 => 'الاثنين', 3 => 'الثلاثاء', 4 => 'الأربعاء', 5 => 'الخميس', 6 => 'الجمعة', 7 => 'السبت'];
        $weekdayRevenue = [];
        foreach ($dayNames as $num => $name) {
            $weekdayRevenue[] = [
                'day_en' => $name,
                'day_ar' => $dayNameAr[$num],
                'avg_revenue' => $dayOfWeek[$num] ?? 0,
            ];
        }

        // ── Simple forecast (next month based on 3-month trend) ─
        $last3 = array_slice($monthlyTrend, -3);
        $avgGrowth = 0;
        if (count($last3) >= 2) {
            $growths = [];
            for ($i = 1; $i < count($last3); $i++) {
                if ($last3[$i - 1]['revenue'] > 0) {
                    $growths[] = ($last3[$i]['revenue'] - $last3[$i - 1]['revenue']) / $last3[$i - 1]['revenue'];
                }
            }
            $avgGrowth = count($growths) ? array_sum($growths) / count($growths) : 0;
        }

        $forecast = [
            'next_month_revenue' => round($currentRevenue * (1 + $avgGrowth), 2),
            'growth_rate' => round($avgGrowth * 100, 1),
            'confidence' => count($monthlyTrend) >= 6 ? 'medium' : 'low',
        ];

        // ── Collection Rate ───────────────────────────────────
        $totalInvoiced = (float) $invoiceQuery()->whereBetween('created_at', [$curStart, $curEnd])->sum('total');
        $totalCollected = (float) $invoiceQuery()->whereBetween('created_at', [$curStart, $curEnd])->sum('paid_amount');
        $collectionRate = $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 1) : 0;

        return Inertia::render('Admin/Reports/RevenueAnalytics', [
            'comparison' => $comparison,
            'monthlyTrend' => $monthlyTrend,
            'topServices' => $topServices,
            'doctorRevenue' => $doctorRevenue,
            'weekdayRevenue' => $weekdayRevenue,
            'forecast' => $forecast,
            'collectionRate' => $collectionRate,
            'totalInvoiced' => round($totalInvoiced, 2),
            'totalCollected' => round($totalCollected, 2),
            'filters' => ['module' => $module],
        ]);
    }
}

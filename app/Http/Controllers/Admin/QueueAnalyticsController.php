<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QueueAnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $baseQuery = fn () => Visit::whereDate('visit_date', '>=', $dateFrom)
            ->whereDate('visit_date', '<=', $dateTo)
            ->where('visits.status', 'completed');

        // ── KPI Stats ─────────────────────────────────────────
        $totalVisits = $baseQuery()->count();
        $avgWait = (float) $baseQuery()->whereNotNull('actual_wait_minutes')->avg('actual_wait_minutes');

        // Calc avg service duration from started_at → completed_at
        $avgServiceMinutes = (float) $baseQuery()
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_duration')
            ->value('avg_duration');

        // No-show rate from booking_appointments
        $totalAppointments = app(\App\Services\Branch\BranchContext::class)
            ->applyToBuilder(DB::table('booking_appointments'), 'booking_appointments.branch_id')
            ->whereDate('appointment_date', '>=', $dateFrom)
            ->whereDate('appointment_date', '<=', $dateTo)
            ->count();
        $noShows = app(\App\Services\Branch\BranchContext::class)
            ->applyToBuilder(DB::table('booking_appointments'), 'booking_appointments.branch_id')
            ->whereDate('appointment_date', '>=', $dateFrom)
            ->whereDate('appointment_date', '<=', $dateTo)
            ->where('status', 'no_show')
            ->count();
        $noShowRate = $totalAppointments > 0 ? round(($noShows / $totalAppointments) * 100, 1) : 0;

        // Visits with wait > 30 min
        $longWaits = $baseQuery()->whereNotNull('actual_wait_minutes')->where('actual_wait_minutes', '>', 30)->count();
        $totalWithWait = $baseQuery()->whereNotNull('actual_wait_minutes')->count();
        $longWaitPct = $totalWithWait > 0 ? round(($longWaits / $totalWithWait) * 100, 1) : 0;

        // ── Wait Time Distribution ────────────────────────────
        $waitDistribution = [
            ['label' => '0-10 min', 'label_ar' => '0-10 دقيقة', 'count' => $baseQuery()->whereNotNull('actual_wait_minutes')->whereBetween('actual_wait_minutes', [0, 10])->count()],
            ['label' => '11-20 min', 'label_ar' => '11-20 دقيقة', 'count' => $baseQuery()->whereNotNull('actual_wait_minutes')->whereBetween('actual_wait_minutes', [11, 20])->count()],
            ['label' => '21-30 min', 'label_ar' => '21-30 دقيقة', 'count' => $baseQuery()->whereNotNull('actual_wait_minutes')->whereBetween('actual_wait_minutes', [21, 30])->count()],
            ['label' => '31-45 min', 'label_ar' => '31-45 دقيقة', 'count' => $baseQuery()->whereNotNull('actual_wait_minutes')->whereBetween('actual_wait_minutes', [31, 45])->count()],
            ['label' => '46-60 min', 'label_ar' => '46-60 دقيقة', 'count' => $baseQuery()->whereNotNull('actual_wait_minutes')->whereBetween('actual_wait_minutes', [46, 60])->count()],
            ['label' => '60+ min', 'label_ar' => '60+ دقيقة', 'count' => $baseQuery()->whereNotNull('actual_wait_minutes')->where('actual_wait_minutes', '>', 60)->count()],
        ];

        // ── Daily Trend ───────────────────────────────────────
        $dailyTrend = $baseQuery()
            ->whereNotNull('actual_wait_minutes')
            ->select(
                DB::raw('DATE(visit_date) as date'),
                DB::raw('ROUND(AVG(actual_wait_minutes), 1) as avg_wait'),
                DB::raw('COUNT(*) as visit_count'),
                DB::raw('MAX(actual_wait_minutes) as max_wait')
            )
            ->groupBy(DB::raw('DATE(visit_date)'))
            ->orderBy('date')
            ->get();

        // ── By Doctor ─────────────────────────────────────────
        $byDoctor = $baseQuery()
            ->whereNotNull('actual_wait_minutes')
            ->join('doctors', 'visits.doctor_id', '=', 'doctors.id')
            ->select(
                'doctors.name_en',
                'doctors.name_ar',
                DB::raw('ROUND(AVG(actual_wait_minutes), 1) as avg_wait'),
                DB::raw('COUNT(*) as visit_count'),
                DB::raw('ROUND(AVG(TIMESTAMPDIFF(MINUTE, visits.started_at, visits.completed_at)), 1) as avg_service')
            )
            ->groupBy('doctors.id', 'doctors.name_en', 'doctors.name_ar')
            ->orderBy('avg_wait')
            ->get();

        // ── Hourly Heatmap ────────────────────────────────────
        $hourlyData = $baseQuery()
            ->whereNotNull('checked_in_at')
            ->select(
                DB::raw('DAYOFWEEK(visit_date) as dow'),
                DB::raw('HOUR(checked_in_at) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('ROUND(AVG(actual_wait_minutes), 1) as avg_wait')
            )
            ->groupBy(DB::raw('DAYOFWEEK(visit_date)'), DB::raw('HOUR(checked_in_at)'))
            ->get()
            ->groupBy('dow')
            ->map(fn ($items) => $items->keyBy('hour'))
            ->toArray();

        // ── Peak Hours ────────────────────────────────────────
        $peakHours = $baseQuery()
            ->whereNotNull('checked_in_at')
            ->select(
                DB::raw('HOUR(checked_in_at) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('ROUND(AVG(actual_wait_minutes), 1) as avg_wait')
            )
            ->groupBy(DB::raw('HOUR(checked_in_at)'))
            ->orderBy('hour')
            ->get();

        return Inertia::render('Admin/Reports/QueueAnalytics', [
            'stats' => [
                'total_visits' => $totalVisits,
                'avg_wait' => round($avgWait, 1),
                'avg_service' => round($avgServiceMinutes, 1),
                'no_show_rate' => $noShowRate,
                'long_wait_pct' => $longWaitPct,
                'total_appointments' => $totalAppointments,
                'no_shows' => $noShows,
            ],
            'waitDistribution' => $waitDistribution,
            'dailyTrend' => $dailyTrend,
            'byDoctor' => $byDoctor,
            'hourlyData' => $hourlyData,
            'peakHours' => $peakHours,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmCampaign;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadSource;
use App\Models\LeadStageHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CrmDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $request->input('period', 'month'); // week, month, quarter, year
        $startDate = match ($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'quarter' => Carbon::now()->startOfQuarter(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        // Pipeline overview
        $pipelineStats = [];
        foreach (Lead::PIPELINE_STATUSES as $status) {
            $pipelineStats[$status] = Lead::where('status', $status)->count();
        }
        $pipelineStats['converted'] = Lead::converted()->count();
        $pipelineStats['lost'] = Lead::lost()->count();

        // Key metrics
        $metrics = [
            'total_leads' => Lead::count(),
            'new_leads_period' => Lead::where('created_at', '>=', $startDate)->count(),
            'converted_period' => Lead::converted()->where('converted_at', '>=', $startDate)->count(),
            'lost_period' => Lead::lost()->where('lost_at', '>=', $startDate)->count(),
            'conversion_rate' => $this->calculateConversionRate($startDate),
            'avg_score' => round(Lead::inPipeline()->avg('score') ?? 0),
            'overdue_follow_ups' => LeadFollowUp::overdue()->count(),
            'today_follow_ups' => LeadFollowUp::today()->count(),
        ];

        // Leads by source
        $leadsBySource = LeadSource::active()
            ->ordered()
            ->withCount(['leads', 'leads as converted_count' => function ($q) {
                $q->where('status', 'converted');
            }])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name_en,
                'color' => $s->color,
                'icon' => $s->icon,
                'total' => $s->leads_count,
                'converted' => $s->converted_count,
                'rate' => $s->leads_count > 0 ? round(($s->converted_count / $s->leads_count) * 100, 1) : 0,
            ]);

        // Recent leads
        $recentLeads = Lead::with(['source:id,name_en,icon,color', 'assignedUser:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        // Today's follow-ups
        $todayFollowUps = LeadFollowUp::with(['lead:id,full_name,phone,status', 'assignedUser:id,name'])
            ->today()
            ->orderBy('scheduled_at')
            ->limit(15)
            ->get();

        // Overdue follow-ups
        $overdueFollowUps = LeadFollowUp::with(['lead:id,full_name,phone,status', 'assignedUser:id,name'])
            ->overdue()
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        // Active campaigns summary
        $activeCampaigns = CrmCampaign::active()
            ->with('leadSource:id,name_en,color')
            ->withCount('leads')
            ->latest()
            ->limit(5)
            ->get();

        // Lead trend (last 30 days)
        $leadTrend = Lead::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // ── Team Performance (top staff) ──
        $teamPerformance = User::active()
            ->whereHas('assignedLeads')
            ->withCount([
                'assignedLeads as total_leads',
                'assignedLeads as active_leads' => fn ($q) => $q->whereNotIn('status', ['converted', 'lost']),
                'assignedLeads as converted_leads' => fn ($q) => $q->where('status', 'converted'),
                'assignedLeads as period_leads' => fn ($q) => $q->where('created_at', '>=', $startDate),
                'assignedLeads as period_converted' => fn ($q) => $q->where('status', 'converted')->where('converted_at', '>=', $startDate),
            ])
            ->get(['id', 'name'])
            ->map(function ($user) use ($startDate) {
                // Overdue follow-ups for this user
                $overdueCount = LeadFollowUp::where('assigned_to', $user->id)
                    ->where('status', 'pending')
                    ->where('scheduled_at', '<', now())
                    ->count();

                // Activities this period
                $periodActivities = LeadActivity::where('performed_by', $user->id)
                    ->where('created_at', '>=', $startDate)
                    ->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_leads' => $user->total_leads,
                    'active_leads' => $user->active_leads,
                    'converted_leads' => $user->converted_leads,
                    'period_leads' => $user->period_leads,
                    'period_converted' => $user->period_converted,
                    'conversion_rate' => $user->total_leads > 0 ? round(($user->converted_leads / $user->total_leads) * 100, 1) : 0,
                    'overdue_follow_ups' => $overdueCount,
                    'period_activities' => $periodActivities,
                ];
            })
            ->sortByDesc('period_converted')
            ->values()
            ->take(8);

        // ── Module Distribution ──
        $moduleDistribution = Lead::whereNotIn('status', ['converted', 'lost'])
            ->selectRaw("COALESCE(module, 'derma') as module, count(*) as count")
            ->groupBy(DB::raw("COALESCE(module, 'derma')"))
            ->pluck('count', 'module')
            ->toArray();

        // ── SLA Metrics (avg response time from creation to first contact) ──
        $avgResponseMinutes = Lead::whereNotNull('first_contacted_at')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_contacted_at)) as avg_minutes')
            ->value('avg_minutes');
        $slaMetrics = [
            'avg_response_minutes' => $avgResponseMinutes ? round($avgResponseMinutes) : null,
            'avg_response_display' => $this->formatMinutes($avgResponseMinutes),
            'awaiting_contact' => Lead::where('status', 'new')->whereNull('first_contacted_at')->count(),
            'within_1h' => Lead::whereNotNull('first_contacted_at')
                ->where('created_at', '>=', $startDate)
                ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, first_contacted_at) <= 60')
                ->count(),
            'total_contacted' => Lead::whereNotNull('first_contacted_at')->where('created_at', '>=', $startDate)->count(),
        ];

        // ── Stale Leads (no activity for 7+ days, still in pipeline) ──
        $staleLeads = Lead::inPipeline()
            ->where(function ($q) {
                $q->where('updated_at', '<', now()->subDays(7))
                    ->orWhere(function ($q2) {
                        $q2->whereNull('last_contacted_at')
                            ->where('created_at', '<', now()->subDays(3));
                    });
            })
            ->with(['assignedUser:id,name', 'source:id,name_en,color'])
            ->orderBy('updated_at')
            ->limit(10)
            ->get(['id', 'full_name', 'phone', 'status', 'priority', 'assigned_to', 'lead_source_id', 'updated_at', 'last_contacted_at', 'created_at']);

        // ── Weekly Comparison ──
        $thisWeekStart = Carbon::now()->startOfWeek();
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();

        $weeklyComparison = [
            'this_week' => [
                'leads' => Lead::where('created_at', '>=', $thisWeekStart)->count(),
                'converted' => Lead::where('status', 'converted')->where('converted_at', '>=', $thisWeekStart)->count(),
                'activities' => LeadActivity::where('created_at', '>=', $thisWeekStart)->count(),
            ],
            'last_week' => [
                'leads' => Lead::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count(),
                'converted' => Lead::where('status', 'converted')->whereBetween('converted_at', [$lastWeekStart, $lastWeekEnd])->count(),
                'activities' => LeadActivity::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count(),
            ],
        ];

        // ── Stage Analytics (time-in-stage) ──
        $stageAnalytics = LeadStageHistory::selectRaw('
            from_status,
            COUNT(*) as transitions,
            AVG(duration_minutes) as avg_minutes,
            MIN(duration_minutes) as min_minutes,
            MAX(duration_minutes) as max_minutes
        ')
            ->whereNotNull('from_status')
            ->whereNotNull('duration_minutes')
            ->where('changed_at', '>=', $startDate)
            ->groupBy('from_status')
            ->get()
            ->keyBy('from_status')
            ->map(fn ($s) => [
                'transitions' => $s->transitions,
                'avg_minutes' => round($s->avg_minutes),
                'avg_display' => $this->formatMinutes($s->avg_minutes),
                'min_display' => $this->formatMinutes($s->min_minutes),
                'max_display' => $this->formatMinutes($s->max_minutes),
            ]);

        // ── Hot leads never contacted (action strip) ──
        $hotUncontacted = Lead::inPipeline()
            ->where('priority', Lead::PRIORITY_HOT)
            ->whereNull('first_contacted_at')
            ->with('source:id,name_en,color')
            ->orderBy('created_at')
            ->limit(8)
            ->get(['id', 'full_name', 'phone', 'status', 'lead_source_id', 'created_at']);

        // ── Upcoming Follow-ups (next 7 days for mini calendar) ──
        $upcomingFollowUps = LeadFollowUp::where('status', 'pending')
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->selectRaw('DATE(scheduled_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return Inertia::render('Admin/CRM/Dashboard', [
            'pipelineStats' => $pipelineStats,
            'metrics' => $metrics,
            'leadsBySource' => $leadsBySource,
            'recentLeads' => $recentLeads,
            'todayFollowUps' => $todayFollowUps,
            'overdueFollowUps' => $overdueFollowUps,
            'activeCampaigns' => $activeCampaigns,
            'leadTrend' => $leadTrend,
            'teamPerformance' => $teamPerformance,
            'moduleDistribution' => $moduleDistribution,
            'weeklyComparison' => $weeklyComparison,
            'slaMetrics' => $slaMetrics,
            'staleLeads' => $staleLeads,
            'hotUncontacted' => $hotUncontacted,
            'upcomingFollowUps' => $upcomingFollowUps,
            'stageAnalytics' => $stageAnalytics,
            'period' => $period,
        ]);
    }

    private function formatMinutes(?float $minutes): string
    {
        if ($minutes === null || $minutes === 0.0) {
            return '-';
        }
        if ($minutes < 60) {
            return round($minutes).'m';
        }
        if ($minutes < 1440) {
            return round($minutes / 60, 1).'h';
        }

        return round($minutes / 1440, 1).'d';
    }

    /**
     * CRM Follow-up Calendar for admins.
     */
    public function calendar(Request $request): Response
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        $calendarStart = $startDate->copy()->startOfWeek(Carbon::SATURDAY);
        $calendarEnd = $endDate->copy()->endOfWeek(Carbon::FRIDAY);

        $followUps = LeadFollowUp::with(['lead:id,full_name,phone,status,priority', 'assignedUser:id,name'])
            ->whereBetween('scheduled_at', [$calendarStart, $calendarEnd])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($fu) => [
                'id' => $fu->id,
                'lead_id' => $fu->lead_id,
                'lead_name' => $fu->lead?->full_name ?? '-',
                'lead_phone' => $fu->lead?->phone ?? '-',
                'lead_status' => $fu->lead?->status ?? '-',
                'lead_priority' => $fu->lead?->priority ?? 3,
                'assigned_to_name' => $fu->assignedUser?->name ?? '-',
                'type' => $fu->type,
                'status' => $fu->status,
                'scheduled_at' => $fu->scheduled_at->toIso8601String(),
                'scheduled_date' => $fu->scheduled_at->format('Y-m-d'),
                'scheduled_time' => $fu->scheduled_at->format('H:i'),
                'notes' => $fu->notes,
                'result' => $fu->result,
                'is_overdue' => $fu->status === 'pending' && $fu->scheduled_at->isPast(),
            ]);

        $monthStats = [
            'total' => LeadFollowUp::whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'pending' => LeadFollowUp::where('status', 'pending')
                ->whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'completed' => LeadFollowUp::where('status', 'completed')
                ->whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'missed' => LeadFollowUp::where('status', 'missed')
                ->whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'overdue' => LeadFollowUp::overdue()->count(),
        ];

        $assignees = User::active()->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/CRM/Calendar', [
            'followUps' => $followUps,
            'monthStats' => $monthStats,
            'assignees' => $assignees,
            'month' => $month,
            'year' => $year,
        ]);
    }

    private function calculateConversionRate(Carbon $startDate): float
    {
        $total = Lead::where('created_at', '>=', $startDate)->count();
        if ($total === 0) {
            return 0;
        }
        $converted = Lead::converted()->where('converted_at', '>=', $startDate)->count();

        return round(($converted / $total) * 100, 1);
    }
}

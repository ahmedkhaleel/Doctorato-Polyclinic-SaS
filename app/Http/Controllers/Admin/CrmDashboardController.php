<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadSource;
use App\Models\CrmCampaign;
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
            'period' => $period,
        ]);
    }

    private function calculateConversionRate(Carbon $startDate): float
    {
        $total = Lead::where('created_at', '>=', $startDate)->count();
        if ($total === 0) return 0;
        $converted = Lead::converted()->where('converted_at', '>=', $startDate)->count();
        return round(($converted / $total) * 100, 1);
    }
}

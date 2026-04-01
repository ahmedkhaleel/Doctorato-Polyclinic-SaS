<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\LeadSource;
use App\Models\CrmCampaign;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        return Inertia::render('Admin/CRM/Dashboard', [
            'pipelineStats' => $pipelineStats,
            'metrics' => $metrics,
            'leadsBySource' => $leadsBySource,
            'recentLeads' => $recentLeads,
            'todayFollowUps' => $todayFollowUps,
            'overdueFollowUps' => $overdueFollowUps,
            'activeCampaigns' => $activeCampaigns,
            'leadTrend' => $leadTrend,
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

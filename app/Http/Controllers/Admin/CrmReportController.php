<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmCampaign;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadSource;
use App\Models\MarketerCommission;
use App\Models\User;
use App\Services\Crm\CrmRevenueService;
use App\Services\ModuleManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());
        $module = $request->input('module');

        // Helper: base lead query with module filter
        $leadBase = fn () => Lead::whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->when($module, fn ($q) => $q->where('module', $module));

        // ── Lead Funnel ──
        $funnel = [
            'total' => $leadBase()->count(),
            'contacted' => $leadBase()
                ->whereIn('status', ['contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation', 'converted'])->count(),
            'qualified' => $leadBase()
                ->whereIn('status', ['qualified', 'appointment_booked', 'consultation_done', 'negotiation', 'converted'])->count(),
            'appointment' => $leadBase()
                ->whereIn('status', ['appointment_booked', 'consultation_done', 'negotiation', 'converted'])->count(),
            'converted' => $leadBase()->where('status', 'converted')->count(),
            'lost' => $leadBase()->where('status', 'lost')->count(),
        ];

        // CRM-1 — real attributed revenue (converted lead → patient → all invoices),
        // single grouped query per dimension instead of the old per-row N+1.
        $revenueService = new CrmRevenueService;
        $sourceRevenue = collect($revenueService->bySource(Carbon::parse($dateFrom)))->keyBy('lead_source_id');
        $campaignRevenue = collect($revenueService->byCampaign(Carbon::parse($dateFrom)))->keyBy('campaign_id');

        // ── By Source ──
        $bySource = LeadSource::active()
            ->ordered()
            ->withCount([
                'leads as total' => fn ($q) => $q->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
                'leads as converted' => fn ($q) => $q->where('status', 'converted')->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
                'leads as lost' => fn ($q) => $q->where('status', 'lost')->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
            ])
            ->get()
            ->map(fn ($s) => [
                'name' => $s->name_en,
                'color' => $s->color,
                'total' => $s->total,
                'converted' => $s->converted,
                'lost' => $s->lost,
                'rate' => $s->total > 0 ? round(($s->converted / $s->total) * 100, 1) : 0,
                'revenue' => (float) ($sourceRevenue[$s->id]['revenue'] ?? 0),
            ]);

        // ── By Campaign ──
        $byCampaign = CrmCampaign::withCount([
            'leads as total' => fn ($q) => $q->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
            'leads as converted' => fn ($q) => $q->where('status', 'converted')->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
        ])
            ->having('total', '>', 0)
            ->get(['id', 'name', 'budget', 'actual_cost'])
            ->map(fn ($c) => [
                'name' => $c->name,
                'total' => $c->total,
                'converted' => $c->converted,
                'rate' => $c->total > 0 ? round(($c->converted / $c->total) * 100, 1) : 0,
                'budget' => $c->budget,
                'cost_per_lead' => $c->total > 0 ? round($c->actual_cost / $c->total, 2) : 0,
            ]);

        // ── Team Performance ──
        $teamPerformance = User::active()
            ->whereHas('assignedLeads', fn ($q) => $q->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)))
            ->withCount([
                'assignedLeads as total_leads' => fn ($q) => $q->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
                'assignedLeads as converted_leads' => fn ($q) => $q->where('status', 'converted')->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
            ])
            ->get(['id', 'name'])
            ->map(fn ($u) => [
                'name' => $u->name,
                'total' => $u->total_leads,
                'converted' => $u->converted_leads,
                'rate' => $u->total_leads > 0 ? round(($u->converted_leads / $u->total_leads) * 100, 1) : 0,
            ]);

        // ── Loss Reasons ──
        $lossReasons = Lead::where('status', 'lost')
            ->whereBetween('lost_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->when($module, fn ($q) => $q->where('module', $module))
            ->whereNotNull('loss_reason')
            ->selectRaw('loss_reason, COUNT(*) as count')
            ->groupBy('loss_reason')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // ── Commissions Summary ──
        $commissionsSummary = [
            'total' => MarketerCommission::whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->sum('commission_amount'),
            'paid' => MarketerCommission::paid()->whereBetween('paid_date', [$dateFrom, $dateTo])->sum('commission_amount'),
            'pending' => MarketerCommission::pending()->sum('commission_amount'),
        ];

        // ── Daily Lead Trend ──
        $dailyTrend = Lead::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->when($module, fn ($q) => $q->where('module', $module))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        // ── Campaign ROI (cost vs revenue from converted leads) ──
        $campaignRoi = CrmCampaign::withCount([
            'leads as total_leads' => fn ($q) => $q->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
            'leads as converted_leads' => fn ($q) => $q->where('status', 'converted')->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
        ])
            ->having('total_leads', '>', 0)
            ->get(['id', 'name', 'budget', 'actual_cost'])
            ->map(function ($campaign) use ($campaignRevenue) {
                // CRM-1: lifetime patient revenue attributed to the campaign's
                // converted leads (was first-booking invoice only, in an N+1 loop).
                $revenue = (float) ($campaignRevenue[$campaign->id]['revenue'] ?? 0);

                $cost = $campaign->actual_cost ?: $campaign->budget ?: 0;
                $roi = $cost > 0 ? round((($revenue - $cost) / $cost) * 100, 1) : 0;

                return [
                    'name' => $campaign->name,
                    'budget' => $campaign->budget,
                    'actual_cost' => $campaign->actual_cost,
                    'total_leads' => $campaign->total_leads,
                    'converted_leads' => $campaign->converted_leads,
                    'revenue' => $revenue,
                    'roi' => $roi,
                    'cost_per_lead' => $campaign->total_leads > 0 ? round(($cost) / $campaign->total_leads, 2) : 0,
                    'cost_per_conversion' => $campaign->converted_leads > 0 ? round(($cost) / $campaign->converted_leads, 2) : 0,
                ];
            });

        // ── Average Conversion Time ──
        $convertedLeads = Lead::where('status', 'converted')
            ->whereNotNull('converted_at')
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->when($module, fn ($q) => $q->where('module', $module))
            ->select('created_at', 'converted_at')
            ->get();

        $conversionTimes = $convertedLeads->map(fn ($l) => Carbon::parse($l->created_at)->diffInDays(Carbon::parse($l->converted_at)));
        $avgConversionDays = $conversionTimes->count() > 0 ? round($conversionTimes->avg(), 1) : 0;
        $minConversionDays = $conversionTimes->count() > 0 ? $conversionTimes->min() : 0;
        $maxConversionDays = $conversionTimes->count() > 0 ? $conversionTimes->max() : 0;
        $medianConversionDays = 0;
        if ($conversionTimes->count() > 0) {
            $sorted = $conversionTimes->sort()->values();
            $mid = intdiv($sorted->count(), 2);
            $medianConversionDays = $sorted->count() % 2 === 0
                ? round(($sorted[$mid - 1] + $sorted[$mid]) / 2, 1)
                : $sorted[$mid];
        }

        // Conversion time distribution (buckets)
        $conversionBuckets = [
            '0-3 days' => $conversionTimes->filter(fn ($d) => $d <= 3)->count(),
            '4-7 days' => $conversionTimes->filter(fn ($d) => $d >= 4 && $d <= 7)->count(),
            '8-14 days' => $conversionTimes->filter(fn ($d) => $d >= 8 && $d <= 14)->count(),
            '15-30 days' => $conversionTimes->filter(fn ($d) => $d >= 15 && $d <= 30)->count(),
            '30+ days' => $conversionTimes->filter(fn ($d) => $d > 30)->count(),
        ];

        $conversionTimeData = [
            'avg' => $avgConversionDays,
            'min' => $minConversionDays,
            'max' => $maxConversionDays,
            'median' => $medianConversionDays,
            'total_converted' => $conversionTimes->count(),
            'buckets' => $conversionBuckets,
        ];

        // ── Staff Performance (enhanced) ──
        $staffPerformance = User::active()
            ->whereHas('assignedLeads', fn ($q) => $q->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)))
            ->withCount([
                'assignedLeads as total_leads' => fn ($q) => $q->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
                'assignedLeads as converted_leads' => fn ($q) => $q->where('status', 'converted')->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])->when($module, fn ($q2) => $q2->where('module', $module)),
            ])
            ->get(['id', 'name'])
            ->map(function ($user) use ($dateFrom, $dateTo) {
                $leadIds = Lead::where('assigned_to', $user->id)
                    ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
                    ->pluck('id');

                // Follow-up count
                $followUpCount = LeadFollowUp::whereIn('lead_id', $leadIds)
                    ->where('assigned_to', $user->id)
                    ->count();

                $completedFollowUps = LeadFollowUp::whereIn('lead_id', $leadIds)
                    ->where('assigned_to', $user->id)
                    ->where('status', 'completed')
                    ->count();

                // Response rate: leads that were contacted (have at least one activity) / total leads
                $contactedLeads = LeadActivity::whereIn('lead_id', $leadIds)
                    ->where('performed_by', $user->id)
                    ->distinct('lead_id')
                    ->count('lead_id');

                $responseRate = $user->total_leads > 0 ? round(($contactedLeads / $user->total_leads) * 100, 1) : 0;

                // Average response time: time from lead creation to first activity
                $avgResponseHours = 0;
                $leadsWithResponse = Lead::where('assigned_to', $user->id)
                    ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
                    ->whereNotNull('last_contacted_at')
                    ->select('created_at', 'last_contacted_at')
                    ->get();

                if ($leadsWithResponse->count() > 0) {
                    $totalHours = $leadsWithResponse->sum(fn ($l) => Carbon::parse($l->created_at)->diffInHours(Carbon::parse($l->last_contacted_at)));
                    $avgResponseHours = round($totalHours / $leadsWithResponse->count(), 1);
                }

                return [
                    'name' => $user->name,
                    'total_leads' => $user->total_leads,
                    'converted_leads' => $user->converted_leads,
                    'conversion_rate' => $user->total_leads > 0 ? round(($user->converted_leads / $user->total_leads) * 100, 1) : 0,
                    'response_rate' => $responseRate,
                    'avg_response_hours' => $avgResponseHours,
                    'follow_up_count' => $followUpCount,
                    'completed_follow_ups' => $completedFollowUps,
                    'follow_up_completion_rate' => $followUpCount > 0 ? round(($completedFollowUps / $followUpCount) * 100, 1) : 0,
                ];
            });

        // ── Monthly Comparison (last 6 months) ──
        $monthlyComparison = collect();
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M Y');

            $total = Lead::whereBetween('created_at', [$monthStart, $monthEnd])->when($module, fn ($q) => $q->where('module', $module))->count();
            $converted = Lead::whereBetween('created_at', [$monthStart, $monthEnd])->when($module, fn ($q) => $q->where('module', $module))->where('status', 'converted')->count();
            $lost = Lead::whereBetween('created_at', [$monthStart, $monthEnd])->when($module, fn ($q) => $q->where('module', $module))->where('status', 'lost')->count();

            $monthlyComparison->push([
                'month' => $monthLabel,
                'month_short' => $monthStart->format('M'),
                'total' => $total,
                'converted' => $converted,
                'lost' => $lost,
                'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0,
            ]);
        }

        return Inertia::render('Admin/CRM/Reports', [
            'funnel' => $funnel,
            'bySource' => $bySource,
            'byCampaign' => $byCampaign,
            'teamPerformance' => $teamPerformance,
            'lossReasons' => $lossReasons,
            'commissionsSummary' => $commissionsSummary,
            'dailyTrend' => $dailyTrend,
            'campaignRoi' => $campaignRoi,
            'conversionTimeData' => $conversionTimeData,
            'staffPerformance' => $staffPerformance,
            'monthlyComparison' => $monthlyComparison,
            'modules' => ModuleManager::getForFrontend(),
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'module' => $module],
        ]);
    }
}

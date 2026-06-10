<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\CrmDormancyScan;
use App\Http\Controllers\Controller;
use App\Models\CrmSetting;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRM-4 — the "Today" work queue: one prioritized page answering
 * "what should the front-desk act on right now?", ordered by urgency:
 * overdue follow-ups → SLA breaches → hot uncontacted → dormancy risk →
 * today's scheduled follow-ups.
 */
class CrmTodayController extends Controller
{
    public function index(): Response
    {
        $leadCols = ['id', 'full_name', 'phone', 'status', 'priority', 'assigned_to', 'lead_source_id', 'created_at', 'last_contacted_at', 'next_follow_up_at'];

        // 1 — Overdue follow-ups (most urgent first = oldest scheduled)
        $overdue = LeadFollowUp::overdue()
            ->with(['lead:id,full_name,phone,status,priority', 'assignedUser:id,name'])
            ->orderBy('scheduled_at')
            ->limit(25)
            ->get(['id', 'lead_id', 'type', 'scheduled_at', 'notes', 'assigned_to'])
            ->filter(fn ($f) => $f->lead !== null)
            ->values();

        // 2 — SLA breaches: new leads past the response target with no first contact
        $slaTargetMinutes = max(1, (int) CrmSetting::get('sla_response_target_minutes', 60));
        $slaBreaches = Lead::where('status', 'new')
            ->whereNull('first_contacted_at')
            ->where('created_at', '<', now()->subMinutes($slaTargetMinutes))
            ->with(['source:id,name_en,color', 'assignedUser:id,name'])
            ->orderBy('created_at')
            ->limit(20)
            ->get($leadCols)
            ->map(function ($lead) {
                $lead->waiting_minutes = (int) $lead->created_at->diffInMinutes(now());

                return $lead;
            });

        // 3 — Hot leads never contacted (excluding the SLA bucket above)
        $slaIds = $slaBreaches->pluck('id');
        $hotUncontacted = Lead::inPipeline()
            ->where('priority', Lead::PRIORITY_HOT)
            ->whereNull('first_contacted_at')
            ->whereNotIn('id', $slaIds)
            ->with(['source:id,name_en,color', 'assignedUser:id,name'])
            ->orderBy('created_at')
            ->limit(15)
            ->get($leadCols);

        // 4 — Dormancy risk (weekly scan cache; high/medium only)
        $dormancy = Cache::get(CrmDormancyScan::CACHE_KEY);
        $seenIds = $slaIds->merge($hotUncontacted->pluck('id'))->merge($overdue->pluck('lead_id'))->all();
        $dormancyLeads = collect($dormancy['leads'] ?? [])
            ->filter(fn ($l) => $l['risk'] !== 'low' && ! in_array($l['id'], $seenIds, true))
            ->take(10)
            ->values();

        // 5 — Today's still-pending follow-ups (not yet overdue)
        $todayFollowUps = LeadFollowUp::today()
            ->where('scheduled_at', '>=', now())
            ->with(['lead:id,full_name,phone,status,priority', 'assignedUser:id,name'])
            ->orderBy('scheduled_at')
            ->limit(25)
            ->get(['id', 'lead_id', 'type', 'scheduled_at', 'notes', 'assigned_to'])
            ->filter(fn ($f) => $f->lead !== null)
            ->values();

        return Inertia::render('Admin/CRM/Today', [
            'overdue' => $overdue,
            'slaBreaches' => $slaBreaches,
            'slaTargetMinutes' => $slaTargetMinutes,
            'hotUncontacted' => $hotUncontacted,
            'dormancyLeads' => $dormancyLeads,
            'dormancyMode' => $dormancy['mode'] ?? null,
            'todayFollowUps' => $todayFollowUps,
        ]);
    }
}

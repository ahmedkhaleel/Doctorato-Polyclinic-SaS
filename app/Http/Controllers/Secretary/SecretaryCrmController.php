<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadScoringRule;
use App\Models\CommunicationTemplate;
use App\Services\CommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryCrmController extends Controller
{
    /**
     * CRM Dashboard for secretary -- shows their assigned leads + follow-ups.
     */
    public function dashboard(): Response
    {
        $userId = auth()->id();

        $stats = [
            'my_leads' => Lead::assignedTo($userId)->whereNotIn('status', ['converted', 'lost'])->count(),
            'new_leads' => Lead::assignedTo($userId)->new()->count(),
            'today_follow_ups' => LeadFollowUp::forUser($userId)->today()->count(),
            'overdue_follow_ups' => LeadFollowUp::forUser($userId)->overdue()->count(),
        ];

        $todayFollowUps = LeadFollowUp::with(['lead:id,full_name,phone,status'])
            ->forUser($userId)
            ->today()
            ->orderBy('scheduled_at')
            ->limit(15)
            ->get();

        $overdueFollowUps = LeadFollowUp::with(['lead:id,full_name,phone,status'])
            ->forUser($userId)
            ->overdue()
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $recentLeads = Lead::with(['source:id,name_en,icon,color'])
            ->assignedTo($userId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Secretary/CRM/Dashboard', [
            'stats' => $stats,
            'todayFollowUps' => $todayFollowUps,
            'overdueFollowUps' => $overdueFollowUps,
            'recentLeads' => $recentLeads,
        ]);
    }

    /**
     * List leads assigned to this secretary.
     */
    public function leads(Request $request): Response
    {
        $userId = auth()->id();

        $query = Lead::with(['source:id,name_en,icon,color', 'campaign:id,name'])
            ->assignedTo($userId);

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $leads = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Secretary/CRM/Leads', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show a single lead detail.
     */
    public function show(Lead $lead): Response
    {
        // Ensure the secretary can only view leads assigned to them
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $lead->load([
            'source:id,name_en,name_ar,icon,color',
            'campaign:id,name',
            'patient:id,full_name,file_number,phone',
        ]);

        $activities = LeadActivity::where('lead_id', $lead->id)
            ->with('performer:id,name')
            ->latest()
            ->limit(30)
            ->get();

        $followUps = LeadFollowUp::where('lead_id', $lead->id)
            ->with(['assignedUser:id,name'])
            ->latest('scheduled_at')
            ->limit(20)
            ->get();

        $templates = CommunicationTemplate::active()
            ->whereIn('channel', ['whatsapp', 'sms', 'email'])
            ->orderBy('channel')
            ->orderBy('name')
            ->get(['id', 'name', 'channel', 'category', 'subject', 'body_en', 'body_ar', 'variables']);

        return Inertia::render('Secretary/CRM/LeadShow', [
            'lead' => $lead,
            'activities' => $activities,
            'followUps' => $followUps,
            'templates' => $templates,
        ]);
    }

    /**
     * Log an activity for a lead.
     */
    public function logActivity(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'type' => 'required|in:note,call,whatsapp,email,sms,meeting',
            'description' => 'nullable|string',
            'direction' => 'nullable|in:inbound,outbound',
            'outcome' => 'nullable|in:successful,no_answer,busy,voicemail,callback_requested,not_interested',
        ]);

        $data['lead_id'] = $lead->id;
        $data['performed_by'] = auth()->id();

        LeadActivity::create($data);
        $lead->markAsContacted();

        // Apply scoring
        if ($data['type'] === 'call') {
            if (($data['outcome'] ?? null) === 'successful') {
                LeadScoringRule::applyToLead($lead, 'call_answered');
            } elseif (($data['outcome'] ?? null) === 'no_answer') {
                LeadScoringRule::applyToLead($lead, 'call_no_answer');
            }
        }
        if ($data['type'] === 'whatsapp' && ($data['direction'] ?? null) === 'inbound') {
            LeadScoringRule::applyToLead($lead, 'whatsapp_replied');
        }

        return back()->with('success', 'Activity logged.');
    }

    /**
     * Schedule a follow-up.
     */
    public function scheduleFollowUp(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'type' => 'required|in:call,whatsapp,email,sms,meeting,other',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string',
        ]);

        $data['lead_id'] = $lead->id;
        $data['assigned_to'] = auth()->id();
        $data['created_by'] = auth()->id();

        LeadFollowUp::create($data);

        $lead->update(['next_follow_up_at' => $data['scheduled_at']]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'follow_up_scheduled',
            'subject' => "Follow-up scheduled: {$data['type']}",
            'performed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Follow-up scheduled.');
    }

    /**
     * Complete a follow-up.
     */
    public function completeFollowUp(Request $request, LeadFollowUp $followUp): RedirectResponse
    {
        if ($followUp->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate(['result' => 'nullable|string|max:500']);

        $followUp->markCompleted($data['result'] ?? null);

        LeadActivity::create([
            'lead_id' => $followUp->lead_id,
            'type' => 'follow_up_completed',
            'subject' => "Follow-up completed: {$followUp->type}",
            'description' => $data['result'] ?? null,
            'performed_by' => auth()->id(),
        ]);

        LeadScoringRule::applyToLead($followUp->lead, 'follow_up_completed');

        return back()->with('success', 'Follow-up completed.');
    }

    /**
     * Mark a follow-up as missed.
     */
    public function missFollowUp(LeadFollowUp $followUp): RedirectResponse
    {
        if ($followUp->assigned_to !== auth()->id()) {
            abort(403);
        }

        $followUp->markMissed();

        LeadActivity::create([
            'lead_id' => $followUp->lead_id,
            'type' => 'system',
            'subject' => "Follow-up missed: {$followUp->type}",
            'performed_by' => auth()->id(),
        ]);

        LeadScoringRule::applyToLead($followUp->lead, 'follow_up_missed');

        return back()->with('success', 'Follow-up marked as missed.');
    }

    /**
     * Update lead status (quick action).
     */
    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'status' => 'required|in:contacted,qualified,appointment_booked,consultation_done,negotiation',
        ]);

        $oldStatus = $lead->status;
        $lead->update(['status' => $data['status']]);

        LeadActivity::logStatusChange($lead, $oldStatus, $data['status']);

        return back()->with('success', 'Lead status updated.');
    }

    /**
     * Quick-send using a template.
     */
    public function quickSend(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'template_id' => 'required|exists:communication_templates,id',
            'channel' => 'required|in:whatsapp,sms,email',
            'language' => 'required|in:en,ar',
        ]);

        $template = CommunicationTemplate::findOrFail($data['template_id']);

        $result = CommunicationService::send(
            lead: $lead,
            template: $template,
            channel: $data['channel'],
            language: $data['language'],
            performedBy: auth()->id(),
        );

        $flash = ['success' => $result['message']];
        if ($result['redirect_url']) {
            $flash['redirect_url'] = $result['redirect_url'];
        }

        if (! $result['success']) {
            $flash = ['error' => $result['message']];
        }

        return back()->with($flash);
    }
}

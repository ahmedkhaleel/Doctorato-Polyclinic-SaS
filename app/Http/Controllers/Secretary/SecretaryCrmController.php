<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Lead;
use App\Models\Patient;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadScoringRule;
use App\Models\CommunicationTemplate;
use App\Models\LeadSource;
use App\Models\CrmCampaign;
use App\Models\Service;
use App\Services\CommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryCrmController extends BaseSecretaryController
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
     * Show form to create a new lead.
     */
    public function create(): Response
    {
        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar', 'icon', 'color']);
        $campaigns = CrmCampaign::where('status', 'active')->get(['id', 'name']);
        $services = Service::where('status', 'active')->get(['id', 'name_en', 'name_ar']);

        return Inertia::render('Secretary/CRM/LeadForm', [
            'sources' => $sources,
            'campaigns' => $campaigns,
            'services' => $services,
            'lead' => null,
        ]);
    }

    /**
     * Store a new lead created by the secretary.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'campaign_id' => 'nullable|exists:crm_campaigns,id',
            'priority' => 'required|in:1,2,3',
            'interested_services' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $data['status'] = 'new';
        $data['created_by'] = auth()->id();
        $data['assigned_to'] = auth()->id();
        $data['assigned_at'] = now();
        $data['score'] = 0;

        $lead = Lead::create($data);

        // Apply initial scoring
        LeadScoringRule::applyToLead($lead, 'lead_created');

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => 'Lead created by secretary',
            'performed_by' => auth()->id(),
        ]);

        return redirect()->route('secretary.crm.show', $lead)
            ->with('success', $this->msg('Lead created successfully.', 'تم إنشاء العميل المحتمل بنجاح.'));
    }

    /**
     * Show form to edit a lead.
     */
    public function edit(Lead $lead): Response
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar', 'icon', 'color']);
        $campaigns = CrmCampaign::where('status', 'active')->get(['id', 'name']);
        $services = Service::where('status', 'active')->get(['id', 'name_en', 'name_ar']);

        return Inertia::render('Secretary/CRM/LeadForm', [
            'sources' => $sources,
            'campaigns' => $campaigns,
            'services' => $services,
            'lead' => $lead,
        ]);
    }

    /**
     * Update an existing lead.
     */
    public function update(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'campaign_id' => 'nullable|exists:crm_campaigns,id',
            'priority' => 'required|in:1,2,3',
            'interested_services' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $lead->update($data);

        return redirect()->route('secretary.crm.show', $lead)
            ->with('success', $this->msg('Lead updated successfully.', 'تم تحديث بيانات العميل بنجاح.'));
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

        return back()->with('success', $this->msg('Activity logged.', 'تم تسجيل النشاط.'));
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

        return back()->with('success', $this->msg('Follow-up scheduled.', 'تم جدولة المتابعة.'));
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

        return back()->with('success', $this->msg('Follow-up completed.', 'تم إكمال المتابعة.'));
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

        return back()->with('success', $this->msg('Follow-up marked as missed.', 'تم تسجيل المتابعة كفائتة.'));
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

        return back()->with('success', $this->msg('Lead status updated.', 'تم تحديث حالة العميل المحتمل.'));
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

    /**
     * Convert a lead to a patient.
     */
    public function convertToPatient(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'booking_notes' => 'nullable|string|max:500',
        ]);

        // If no existing patient provided, create a new one
        if (empty($data['patient_id'])) {
            $patient = Patient::create([
                'full_name' => $lead->full_name,
                'phone' => $lead->phone,
                'phone2' => $lead->phone2,
                'email' => $lead->email,
                'gender' => $lead->gender,
                'date_of_birth' => $lead->date_of_birth,
                'city' => $lead->city,
                'nationality' => $lead->nationality,
                'notes' => $data['booking_notes'] ?? null,
            ]);
        } else {
            $patient = Patient::findOrFail($data['patient_id']);
        }

        $lead->convertToPatient($patient);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => 'Lead converted to patient',
            'description' => 'Patient file #' . ($patient->file_number ?? $patient->id),
            'performed_by' => auth()->id(),
        ]);

        return back()->with('success', $this->msg(
            'Lead successfully converted to patient!',
            'تم تحويل العميل المحتمل إلى مريض بنجاح!'
        ));
    }

    /**
     * Mark a lead as lost.
     */
    public function markAsLost(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'loss_reason' => 'required|string|max:500',
        ]);

        $oldStatus = $lead->status;
        $lead->markAsLost($data['loss_reason']);

        LeadActivity::logStatusChange($lead, $oldStatus, 'lost');

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => 'Lead marked as lost',
            'description' => $data['loss_reason'],
            'performed_by' => auth()->id(),
        ]);

        return back()->with('success', $this->msg(
            'Lead marked as lost.',
            'تم تسجيل العميل كخسارة.'
        ));
    }

    /**
     * Reschedule a follow-up.
     */
    public function rescheduleFollowUp(Request $request, LeadFollowUp $followUp): RedirectResponse
    {
        if ($followUp->assigned_to !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:500',
        ]);

        $followUp->reschedule(new \DateTime($data['scheduled_at']));

        // Update new follow-up notes if provided
        if (!empty($data['notes'])) {
            $newFollowUp = LeadFollowUp::where('lead_id', $followUp->lead_id)
                ->where('status', 'pending')
                ->latest()
                ->first();
            if ($newFollowUp) {
                $newFollowUp->update(['notes' => $data['notes']]);
            }
        }

        // Update lead next follow-up
        $followUp->lead->update(['next_follow_up_at' => $data['scheduled_at']]);

        LeadActivity::create([
            'lead_id' => $followUp->lead_id,
            'type' => 'follow_up_scheduled',
            'subject' => 'Follow-up rescheduled',
            'performed_by' => auth()->id(),
        ]);

        return back()->with('success', $this->msg(
            'Follow-up rescheduled successfully.',
            'تم إعادة جدولة المتابعة بنجاح.'
        ));
    }

    /**
     * Check for duplicate leads by phone number (AJAX).
     */
    public function checkDuplicate(Request $request)
    {
        $phone = $request->input('phone');
        if (!$phone) {
            return response()->json(['exists' => false]);
        }

        $existing = Lead::where('phone', $phone)
            ->orWhere('phone2', $phone)
            ->first(['id', 'full_name', 'phone', 'status', 'assigned_to']);

        return response()->json([
            'exists' => !!$existing,
            'lead' => $existing ? [
                'id' => $existing->id,
                'full_name' => $existing->full_name,
                'phone' => $existing->phone,
                'status' => $existing->status,
                'is_mine' => $existing->assigned_to === auth()->id(),
            ] : null,
        ]);
    }

    /**
     * Performance report page.
     */
    public function performance(): Response
    {
        $userId = auth()->id();
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();

        // Monthly stats
        $monthlyStats = [
            'calls' => LeadActivity::where('performed_by', $userId)
                ->where('type', 'call')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'whatsapp' => LeadActivity::where('performed_by', $userId)
                ->where('type', 'whatsapp')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'emails' => LeadActivity::where('performed_by', $userId)
                ->where('type', 'email')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'meetings' => LeadActivity::where('performed_by', $userId)
                ->where('type', 'meeting')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'total_activities' => LeadActivity::where('performed_by', $userId)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'follow_ups_completed' => LeadFollowUp::forUser($userId)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startOfMonth)
                ->count(),
            'follow_ups_missed' => LeadFollowUp::forUser($userId)
                ->where('status', 'missed')
                ->where('updated_at', '>=', $startOfMonth)
                ->count(),
            'leads_created' => Lead::where('created_by', $userId)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'leads_converted' => Lead::assignedTo($userId)
                ->where('status', 'converted')
                ->where('converted_at', '>=', $startOfMonth)
                ->count(),
            'leads_lost' => Lead::assignedTo($userId)
                ->where('status', 'lost')
                ->where('lost_at', '>=', $startOfMonth)
                ->count(),
        ];

        // Weekly stats
        $weeklyStats = [
            'calls' => LeadActivity::where('performed_by', $userId)
                ->where('type', 'call')
                ->where('created_at', '>=', $startOfWeek)
                ->count(),
            'total_activities' => LeadActivity::where('performed_by', $userId)
                ->where('created_at', '>=', $startOfWeek)
                ->count(),
            'follow_ups_completed' => LeadFollowUp::forUser($userId)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startOfWeek)
                ->count(),
        ];

        // Daily activity for the last 7 days (for chart)
        $dailyActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $dailyActivity[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('D'),
                'label_ar' => $day->locale('ar')->dayName,
                'count' => LeadActivity::where('performed_by', $userId)
                    ->whereDate('created_at', $day->format('Y-m-d'))
                    ->count(),
            ];
        }

        // Status distribution of my active leads
        $statusDistribution = Lead::assignedTo($userId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Conversion rate
        $totalAssigned = Lead::assignedTo($userId)->count();
        $totalConverted = Lead::assignedTo($userId)->where('status', 'converted')->count();
        $conversionRate = $totalAssigned > 0 ? round(($totalConverted / $totalAssigned) * 100, 1) : 0;

        return Inertia::render('Secretary/CRM/Performance', [
            'monthlyStats' => $monthlyStats,
            'weeklyStats' => $weeklyStats,
            'dailyActivity' => $dailyActivity,
            'statusDistribution' => $statusDistribution,
            'conversionRate' => $conversionRate,
        ]);
    }
}

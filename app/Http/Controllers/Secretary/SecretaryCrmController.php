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
use App\Imports\LeadsImport;
use App\Services\CommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Status distribution for mini funnel
        $statusDistribution = Lead::assignedTo($userId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Recent activities (last 10)
        $recentActivities = LeadActivity::with(['lead:id,full_name'])
            ->where('performed_by', $userId)
            ->latest()
            ->limit(10)
            ->get(['id', 'lead_id', 'type', 'subject', 'created_at']);

        // Today's activity count
        $todayActivityCount = LeadActivity::where('performed_by', $userId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        // Conversion stats
        $totalAssigned = Lead::assignedTo($userId)->count();
        $totalConverted = Lead::assignedTo($userId)->where('status', 'converted')->count();
        $conversionRate = $totalAssigned > 0 ? round(($totalConverted / $totalAssigned) * 100, 1) : 0;

        return Inertia::render('Secretary/CRM/Dashboard', [
            'stats' => $stats,
            'todayFollowUps' => $todayFollowUps,
            'overdueFollowUps' => $overdueFollowUps,
            'recentLeads' => $recentLeads,
            'statusDistribution' => $statusDistribution,
            'recentActivities' => $recentActivities,
            'todayActivityCount' => $todayActivityCount,
            'conversionRate' => $conversionRate,
        ]);
    }

    /**
     * List leads assigned to this secretary.
     */
    public function leads(Request $request): Response
    {
        $userId = auth()->id();

        $query = Lead::with(['source:id,name_en,name_ar,icon,color', 'campaign:id,name'])
            ->assignedTo($userId);

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($source = $request->input('source')) {
            $query->where('lead_source_id', $source);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Sort
        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['created_at', 'full_name', 'score', 'priority', 'next_follow_up_at'];
        if (in_array($sortField, $allowedSorts) && in_array($sortDir, ['asc', 'desc'])) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $leads = $query->paginate(20)->withQueryString();

        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar', 'color']);

        return Inertia::render('Secretary/CRM/Leads', [
            'leads' => $leads,
            'sources' => $sources,
            'filters' => $request->only(['search', 'status', 'priority', 'source', 'date_from', 'date_to', 'sort', 'dir']),
        ]);
    }

    /**
     * Bulk update status for multiple leads.
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer|exists:leads,id',
            'status' => 'required|in:new,contacted,qualified,appointment_booked,consultation_done,negotiation',
        ]);

        $userId = auth()->id();
        $updated = 0;

        foreach ($data['lead_ids'] as $leadId) {
            $lead = Lead::where('id', $leadId)->where('assigned_to', $userId)->first();
            if ($lead && $lead->status !== $data['status']) {
                $oldStatus = $lead->status;
                $lead->update(['status' => $data['status']]);
                LeadActivity::logStatusChange($lead, $oldStatus, $data['status']);
                $updated++;
            }
        }

        return back()->with('success', $this->msg(
            "{$updated} leads updated successfully.",
            "تم تحديث {$updated} عميل بنجاح."
        ));
    }

    /**
     * Bulk update priority for selected leads.
     */
    public function bulkUpdatePriority(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer|exists:leads,id',
            'priority' => 'required|in:1,2,3',
        ]);

        $userId = auth()->id();
        $updated = 0;

        foreach ($data['lead_ids'] as $leadId) {
            $lead = Lead::where('id', $leadId)->where('assigned_to', $userId)->first();
            if ($lead && $lead->priority != $data['priority']) {
                $lead->update(['priority' => $data['priority']]);
                $updated++;
            }
        }

        return back()->with('success', $this->msg(
            "{$updated} leads priority updated successfully.",
            "تم تحديث أولوية {$updated} عميل بنجاح."
        ));
    }

    /**
     * Quick view lead data (AJAX).
     */
    public function quickView(Lead $lead)
    {
        if ($lead->assigned_to !== auth()->id()) {
            abort(403);
        }

        $activities = LeadActivity::where('lead_id', $lead->id)
            ->with('performer:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $followUps = LeadFollowUp::where('lead_id', $lead->id)
            ->where('status', 'pending')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return response()->json([
            'activities' => $activities,
            'followUps' => $followUps,
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
     * Calendar view for follow-ups.
     */
    public function calendar(Request $request): Response
    {
        $userId = auth()->id();

        // Default to current month
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        // Get follow-ups for the month (include a week before/after for calendar edges)
        $calendarStart = $startDate->copy()->startOfWeek(\Carbon\Carbon::SATURDAY);
        $calendarEnd = $endDate->copy()->endOfWeek(\Carbon\Carbon::FRIDAY);

        $followUps = LeadFollowUp::with(['lead:id,full_name,phone,status,priority'])
            ->forUser($userId)
            ->whereBetween('scheduled_at', [$calendarStart, $calendarEnd])
            ->orderBy('scheduled_at')
            ->get()
            ->map(function ($fu) {
                return [
                    'id' => $fu->id,
                    'lead_id' => $fu->lead_id,
                    'lead_name' => $fu->lead?->full_name ?? '-',
                    'lead_phone' => $fu->lead?->phone ?? '-',
                    'lead_status' => $fu->lead?->status ?? '-',
                    'lead_priority' => $fu->lead?->priority ?? 3,
                    'type' => $fu->type,
                    'status' => $fu->status,
                    'scheduled_at' => $fu->scheduled_at->toIso8601String(),
                    'scheduled_date' => $fu->scheduled_at->format('Y-m-d'),
                    'scheduled_time' => $fu->scheduled_at->format('H:i'),
                    'notes' => $fu->notes,
                    'result' => $fu->result,
                    'is_overdue' => $fu->is_overdue,
                ];
            });

        // Stats for the header
        $monthStats = [
            'total' => LeadFollowUp::forUser($userId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'pending' => LeadFollowUp::forUser($userId)->pending()
                ->whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'completed' => LeadFollowUp::forUser($userId)
                ->where('status', 'completed')
                ->whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'missed' => LeadFollowUp::forUser($userId)
                ->where('status', 'missed')
                ->whereBetween('scheduled_at', [$startDate, $endDate])->count(),
            'overdue' => LeadFollowUp::forUser($userId)->overdue()->count(),
        ];

        // Active leads for quick-add
        $activeLeads = Lead::assignedTo($userId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone']);

        return Inertia::render('Secretary/CRM/Calendar', [
            'followUps' => $followUps,
            'monthStats' => $monthStats,
            'currentMonth' => $month,
            'currentYear' => $year,
            'activeLeads' => $activeLeads,
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
        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
        $endOfLastWeek = $now->copy()->subWeek()->endOfWeek();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

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

        // Weekly stats (this week + last week for comparison)
        $weeklyStats = [
            'total_activities' => LeadActivity::where('performed_by', $userId)
                ->where('created_at', '>=', $startOfWeek)->count(),
            'calls' => LeadActivity::where('performed_by', $userId)
                ->where('type', 'call')->where('created_at', '>=', $startOfWeek)->count(),
            'follow_ups_completed' => LeadFollowUp::forUser($userId)
                ->where('status', 'completed')->where('completed_at', '>=', $startOfWeek)->count(),
            'leads_converted' => Lead::assignedTo($userId)
                ->where('status', 'converted')->where('converted_at', '>=', $startOfWeek)->count(),
        ];

        $lastWeekStats = [
            'total_activities' => LeadActivity::where('performed_by', $userId)
                ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count(),
            'calls' => LeadActivity::where('performed_by', $userId)
                ->where('type', 'call')->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count(),
            'follow_ups_completed' => LeadFollowUp::forUser($userId)
                ->where('status', 'completed')->whereBetween('completed_at', [$startOfLastWeek, $endOfLastWeek])->count(),
            'leads_converted' => Lead::assignedTo($userId)
                ->where('status', 'converted')->whereBetween('converted_at', [$startOfLastWeek, $endOfLastWeek])->count(),
        ];

        // Daily activity for the last 14 days
        $dailyActivity = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $dailyActivity[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('D'),
                'label_ar' => $day->locale('ar')->dayName,
                'day_num' => $day->format('d'),
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

        // Average response time (hours) - time between lead creation and first activity
        $avgResponseTime = DB::table('leads')
            ->join('lead_activities', function ($join) use ($userId) {
                $join->on('leads.id', '=', 'lead_activities.lead_id')
                     ->where('lead_activities.performed_by', $userId);
            })
            ->where('leads.assigned_to', $userId)
            ->where('leads.created_at', '>=', $startOfMonth)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, leads.created_at, lead_activities.created_at)) as avg_hours')
            ->value('avg_hours');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : null;

        // Activity streak (consecutive days with at least 1 activity, going backwards from today)
        $streak = 0;
        for ($i = 0; $i < 60; $i++) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $hasActivity = LeadActivity::where('performed_by', $userId)
                ->whereDate('created_at', $day)
                ->exists();
            if ($hasActivity) {
                $streak++;
            } else {
                break;
            }
        }

        // Overdue follow-ups count
        $overdueFollowUps = LeadFollowUp::forUser($userId)
            ->where('status', 'pending')
            ->where('scheduled_at', '<', $now)
            ->count();

        // Today's pending follow-ups
        $todayFollowUps = LeadFollowUp::forUser($userId)
            ->where('status', 'pending')
            ->whereDate('scheduled_at', $now->toDateString())
            ->count();

        // Source performance (top 5)
        $sourcePerformance = DB::table('leads')
            ->join('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->where('leads.assigned_to', $userId)
            ->selectRaw('lead_sources.name_en, lead_sources.name_ar, lead_sources.color, COUNT(*) as total, SUM(CASE WHEN leads.status = "converted" THEN 1 ELSE 0 END) as converted')
            ->groupBy('lead_sources.id', 'lead_sources.name_en', 'lead_sources.name_ar', 'lead_sources.color')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($s) {
                $s->rate = $s->total > 0 ? round(($s->converted / $s->total) * 100, 1) : 0;
                return $s;
            });

        return Inertia::render('Secretary/CRM/Performance', [
            'monthlyStats' => $monthlyStats,
            'weeklyStats' => $weeklyStats,
            'lastWeekStats' => $lastWeekStats,
            'dailyActivity' => $dailyActivity,
            'statusDistribution' => $statusDistribution,
            'conversionRate' => $conversionRate,
            'avgResponseTime' => $avgResponseTime,
            'streak' => $streak,
            'overdueFollowUps' => $overdueFollowUps,
            'todayFollowUps' => $todayFollowUps,
            'sourcePerformance' => $sourcePerformance,
        ]);
    }

    /**
     * Show the bulk import page.
     */
    public function importPage(): Response
    {
        return Inertia::render('Secretary/CRM/Import');
    }

    /**
     * Preview uploaded Excel/CSV file (returns headers + first rows).
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // max 5MB
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\WithHeadingRow {}, $file);

            if (empty($data) || empty($data[0])) {
                return response()->json(['error' => 'Empty file or no data found.'], 422);
            }

            $rows = $data[0];
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
            $preview = array_slice($rows, 0, 5); // First 5 rows for preview

            return response()->json([
                'headers' => $headers,
                'preview' => $preview,
                'total_rows' => count($rows),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Process the bulk import.
     */
    public function importProcess(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'column_map' => 'required|array',
            'column_map.full_name' => 'required|string',
            'column_map.phone' => 'required|string',
        ]);

        $columnMap = $request->input('column_map', []);

        $import = new LeadsImport(auth()->id(), $columnMap);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', $this->msg(
                'Import failed: ' . $e->getMessage(),
                'فشل الاستيراد: ' . $e->getMessage()
            ));
        }

        $imported = $import->getImported();
        $skipped = $import->getSkipped();
        $errors = $import->getErrors();

        // Store errors in session for display
        if (!empty($errors)) {
            session()->flash('import_errors', $errors);
        }

        return redirect()->route('secretary.crm.leads')
            ->with('success', $this->msg(
                "Import completed: {$imported} leads imported, {$skipped} skipped.",
                "اكتمل الاستيراد: {$imported} عميل تم استيرادهم، {$skipped} تم تخطيهم."
            ));
    }

    /**
     * Download a sample import template.
     */
    public function importTemplate()
    {
        $headers = ['full_name', 'phone', 'phone2', 'email', 'gender', 'date_of_birth', 'city', 'nationality', 'priority', 'notes'];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            // Sample row
            fputcsv($file, ['Ahmed Mohammed', '+966501234567', '', 'ahmed@email.com', 'male', '1990-01-15', 'Riyadh', 'Saudi', 'hot', 'Interested in laser treatment']);
            fputcsv($file, ['Sara Ali', '+966509876543', '', 'sara@email.com', 'female', '1985-06-20', 'Jeddah', 'Saudi', 'warm', '']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="leads_import_template.csv"',
        ]);
    }

    /**
     * Pipeline / Kanban view of leads.
     */
    public function pipeline(): Response
    {
        $userId = auth()->id();

        $statuses = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];

        $columns = [];
        foreach ($statuses as $status) {
            $columns[$status] = Lead::with(['source:id,name_en,name_ar,icon,color'])
                ->assignedTo($userId)
                ->where('status', $status)
                ->orderByDesc('priority')
                ->orderByDesc('updated_at')
                ->get(['id', 'full_name', 'phone', 'email', 'status', 'priority', 'score', 'lead_source_id', 'next_follow_up_at', 'last_contacted_at', 'created_at', 'updated_at'])
                ->toArray();
        }

        return Inertia::render('Secretary/CRM/Pipeline', [
            'columns' => $columns,
        ]);
    }

    /**
     * Export leads as CSV.
     */
    public function export(Request $request)
    {
        $userId = auth()->id();

        $query = Lead::with(['source:id,name_en'])
            ->assignedTo($userId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $leads = $query->orderByDesc('created_at')->get();

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID', 'Full Name', 'Phone', 'Phone 2', 'Email', 'Gender',
                'City', 'Nationality', 'Status', 'Priority', 'Score',
                'Source', 'Last Contacted', 'Next Follow-up', 'Created At',
            ]);

            $priorityMap = [1 => 'Hot', 2 => 'Warm', 3 => 'Cold'];

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->full_name,
                    $lead->phone,
                    $lead->phone2,
                    $lead->email,
                    $lead->gender,
                    $lead->city,
                    $lead->nationality,
                    $lead->status,
                    $priorityMap[$lead->priority] ?? 'Cold',
                    $lead->score,
                    $lead->source?->name_en ?? '-',
                    $lead->last_contacted_at ? $lead->last_contacted_at->format('Y-m-d H:i') : '-',
                    $lead->next_follow_up_at ? $lead->next_follow_up_at->format('Y-m-d H:i') : '-',
                    $lead->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        $filename = 'my_leads_' . now()->format('Y-m-d') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Communication Templates page — browse & use templates.
     */
    public function templates(Request $request): Response
    {
        $query = CommunicationTemplate::active();

        if ($channel = $request->input('channel')) {
            $query->forChannel($channel);
        }
        if ($category = $request->input('category')) {
            $query->forCategory($category);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('body_en', 'like', "%{$search}%")
                  ->orWhere('body_ar', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderByDesc('usage_count')->get();

        // Get active leads for "send to" selection
        $myLeads = Lead::assignedTo(auth()->id())
            ->whereNotIn('status', ['converted', 'lost'])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone', 'email', 'status']);

        return Inertia::render('Secretary/CRM/Templates', [
            'templates' => $templates,
            'leads' => $myLeads,
            'filters' => $request->only(['channel', 'category', 'search']),
        ]);
    }

    /**
     * Duplicate a communication template.
     */
    public function duplicateTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            '_duplicate' => 'required|integer|exists:communication_templates,id',
            'name' => 'required|string|max:255',
        ]);

        $original = CommunicationTemplate::findOrFail($data['_duplicate']);

        CommunicationTemplate::create([
            'name' => $data['name'],
            'channel' => $original->channel,
            'category' => $original->category,
            'body_ar' => $original->body_ar,
            'body_en' => $original->body_en,
            'variables' => $original->variables,
            'is_active' => true,
            'usage_count' => 0,
        ]);

        return back()->with('success', $this->msg(
            'Template duplicated successfully.',
            'تم تكرار القالب بنجاح.'
        ));
    }

    /**
     * Preview a rendered template with lead data (AJAX).
     */
    public function templatePreview(Request $request)
    {
        $data = $request->validate([
            'template_id' => 'required|exists:communication_templates,id',
            'lead_id' => 'required|exists:leads,id',
            'language' => 'required|in:en,ar',
        ]);

        $template = CommunicationTemplate::findOrFail($data['template_id']);
        $lead = Lead::findOrFail($data['lead_id']);

        if ($lead->assigned_to !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $variables = [
            'name' => $lead->full_name,
            'first_name' => explode(' ', $lead->full_name)[0] ?? $lead->full_name,
            'phone' => $lead->phone,
            'email' => $lead->email ?? '',
            'clinic_name' => config('app.name', 'Aura Derma Clinic'),
            'status' => $lead->status,
        ];

        $rendered = $template->renderBody($data['language'], $variables);

        return response()->json([
            'rendered' => $rendered,
            'subject' => $template->subject,
            'channel' => $template->channel,
        ]);
    }

    /**
     * CRM Reports — advanced analytics.
     */
    public function reports(Request $request): Response
    {
        $userId = auth()->id();
        $now = Carbon::now();

        // Period: default last 30 days
        $period = $request->input('period', '30');
        $startDate = match ($period) {
            '7' => $now->copy()->subDays(7)->startOfDay(),
            '30' => $now->copy()->subDays(30)->startOfDay(),
            '90' => $now->copy()->subDays(90)->startOfDay(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->subDays(30)->startOfDay(),
        };

        // ── Conversion Funnel ──
        $funnel = [];
        $funnelStatuses = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation', 'converted'];
        foreach ($funnelStatuses as $status) {
            $q = Lead::assignedTo($userId)->where('created_at', '>=', $startDate);
            // For converted/lost, check if they ever reached this status
            if ($status === 'converted') {
                $funnel[$status] = (clone $q)->where('status', 'converted')->count();
            } else {
                // Count leads currently at OR past this status
                $statusOrder = array_search($status, $funnelStatuses);
                $validStatuses = array_slice($funnelStatuses, $statusOrder);
                $validStatuses[] = 'lost'; // Include lost from any stage
                $funnel[$status] = (clone $q)->whereIn('status', $validStatuses)->count();
            }
        }

        // ── Source Performance ──
        $sourcePerformance = Lead::assignedTo($userId)
            ->where('leads.created_at', '>=', $startDate)
            ->join('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->selectRaw('lead_sources.name_en, lead_sources.name_ar, lead_sources.color, lead_sources.icon,
                          count(*) as total,
                          sum(case when leads.status = "converted" then 1 else 0 end) as converted,
                          sum(case when leads.status = "lost" then 1 else 0 end) as lost,
                          avg(leads.score) as avg_score')
            ->groupBy('lead_sources.id', 'lead_sources.name_en', 'lead_sources.name_ar', 'lead_sources.color', 'lead_sources.icon')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($s) => [
                'name_en' => $s->name_en,
                'name_ar' => $s->name_ar,
                'color' => $s->color,
                'icon' => $s->icon,
                'total' => $s->total,
                'converted' => (int) $s->converted,
                'lost' => (int) $s->lost,
                'avg_score' => round($s->avg_score ?? 0),
                'conversion_rate' => $s->total > 0 ? round(($s->converted / $s->total) * 100, 1) : 0,
            ]);

        // ── Activity Trend (daily for period) ──
        $activityDays = min((int) $period === 0 ? 30 : (int) $period, 90);
        $activityTrend = [];
        for ($i = $activityDays - 1; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $activityTrend[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('M d'),
                'label_ar' => $day->locale('ar')->format('d M'),
                'activities' => LeadActivity::where('performed_by', $userId)
                    ->whereDate('created_at', $day->format('Y-m-d'))
                    ->count(),
                'follow_ups' => LeadFollowUp::forUser($userId)
                    ->where('status', 'completed')
                    ->whereDate('completed_at', $day->format('Y-m-d'))
                    ->count(),
            ];
        }

        // ── Response Time (avg hours from lead creation to first contact) ──
        $avgResponseHours = Lead::assignedTo($userId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('last_contacted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, last_contacted_at)) as avg_hours')
            ->value('avg_hours');

        // ── Top leads by score ──
        $topLeads = Lead::assignedTo($userId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->orderByDesc('score')
            ->limit(10)
            ->get(['id', 'full_name', 'phone', 'status', 'priority', 'score', 'next_follow_up_at']);

        // ── Period comparison ──
        $prevStart = $startDate->copy()->subDays($activityDays);
        $currentActivities = LeadActivity::where('performed_by', $userId)
            ->where('created_at', '>=', $startDate)->count();
        $prevActivities = LeadActivity::where('performed_by', $userId)
            ->whereBetween('created_at', [$prevStart, $startDate])->count();

        $currentConverted = Lead::assignedTo($userId)->where('status', 'converted')
            ->where('converted_at', '>=', $startDate)->count();
        $prevConverted = Lead::assignedTo($userId)->where('status', 'converted')
            ->whereBetween('converted_at', [$prevStart, $startDate])->count();

        $currentLeads = Lead::assignedTo($userId)
            ->where('created_at', '>=', $startDate)->count();
        $prevLeads = Lead::assignedTo($userId)
            ->whereBetween('created_at', [$prevStart, $startDate])->count();

        $comparison = [
            'activities' => ['current' => $currentActivities, 'previous' => $prevActivities],
            'converted' => ['current' => $currentConverted, 'previous' => $prevConverted],
            'new_leads' => ['current' => $currentLeads, 'previous' => $prevLeads],
        ];

        return Inertia::render('Secretary/CRM/Reports', [
            'funnel' => $funnel,
            'sourcePerformance' => $sourcePerformance,
            'activityTrend' => $activityTrend,
            'avgResponseHours' => round($avgResponseHours ?? 0, 1),
            'topLeads' => $topLeads,
            'comparison' => $comparison,
            'period' => $period,
        ]);
    }
}

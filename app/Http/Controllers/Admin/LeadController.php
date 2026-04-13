<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadScoringRule;
use App\Models\LeadSource;
use App\Models\CrmCampaign;
use App\Models\CommunicationTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CommunicationService;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Lead::with(['source:id,name_en,name_ar,icon,color', 'assignedUser:id,name', 'campaign:id,name']);

        // Module filter
        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($sourceId = $request->input('source_id')) {
            $query->where('lead_source_id', $sourceId);
        }

        if ($assignedTo = $request->input('assigned_to')) {
            if ($assignedTo === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', (int) $assignedTo);
            }
        }

        if ($campaignId = $request->input('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $leads = $query->latest()->paginate(20)->withQueryString();

        // Pipeline stats
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::new()->count(),
            'in_pipeline' => Lead::inPipeline()->count(),
            'converted' => Lead::converted()->count(),
            'lost' => Lead::lost()->count(),
            'hot' => Lead::hot()->whereNotIn('status', ['converted', 'lost'])->count(),
            'overdue_follow_ups' => Lead::overdueFollowUp()->count(),
        ];

        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar', 'icon', 'color']);
        $campaigns = CrmCampaign::latest()->get(['id', 'name']);
        $assignees = User::active()->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/CRM/Leads/Index', [
            'leads' => $leads,
            'stats' => $stats,
            'sources' => $sources,
            'campaigns' => $campaigns,
            'assignees' => $assignees,
            'filters' => $request->only(['search', 'status', 'priority', 'source_id', 'assigned_to', 'campaign_id', 'date_from', 'date_to', 'module']),
        ]);
    }

    public function create(): Response
    {
        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar', 'icon', 'color']);
        $campaigns = CrmCampaign::where('status', 'active')->get(['id', 'name']);
        $assignees = User::active()->select('id', 'name')->orderBy('name')->get();
        $services = \App\Models\Service::where('status', 'active')->get(['id', 'name_en', 'name_ar']);

        return Inertia::render('Admin/CRM/Leads/Create', [
            'sources' => $sources,
            'campaigns' => $campaigns,
            'assignees' => $assignees,
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'campaign_id' => 'nullable|exists:crm_campaigns,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:1,2,3',
            'interested_services' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $data['status'] = 'new';
        $data['created_by'] = auth()->id();
        $data['score'] = 0;

        if (!empty($data['assigned_to'])) {
            $data['assigned_at'] = now();
        }

        $lead = Lead::create($data);

        // Apply initial scoring
        LeadScoringRule::applyToLead($lead, 'lead_created');
        if ($lead->phone) {
            LeadScoringRule::applyToLead($lead, 'phone_provided');
        }
        if ($lead->email) {
            LeadScoringRule::applyToLead($lead, 'email_provided');
        }

        // Update campaign counter
        if ($lead->campaign_id) {
            CrmCampaign::where('id', $lead->campaign_id)->increment('leads_count');
        }

        // Auto-assign if not manually assigned
        if (! $lead->assigned_to) {
            LeadService::autoAssign($lead);
        }

        // Log activity
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => 'Lead created',
            'performed_by' => auth()->id(),
        ]);

        // Trigger automation sequences
        \App\Services\FollowUpAutomationService::triggerEvent($lead, \App\Models\FollowUpSequence::EVENT_LEAD_CREATED);

        return redirect()->route('admin.leads.show', $lead)->with('success', 'Lead created successfully.');
    }

    public function show(Lead $lead): Response
    {
        $lead->load([
            'source:id,name_en,name_ar,icon,color',
            'campaign:id,name',
            'assignedUser:id,name,email',
            'patient:id,full_name,file_number,phone',
            'booking',
            'creator:id,name',
        ]);

        $activities = LeadActivity::where('lead_id', $lead->id)
            ->with('performer:id,name')
            ->latest()
            ->limit(50)
            ->get();

        $followUps = LeadFollowUp::where('lead_id', $lead->id)
            ->with(['assignedUser:id,name'])
            ->latest('scheduled_at')
            ->limit(20)
            ->get();

        $assignees = User::active()->select('id', 'name')->orderBy('name')->get();
        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar']);
        $services = \App\Models\Service::where('status', 'active')->get(['id', 'name_en', 'name_ar', 'session_duration_minutes', 'price', 'module']);
        $doctors = \App\Models\Doctor::where('status', 'active')
            ->get(['id', 'name_en', 'name_ar', 'specialization_en', 'specialization_ar', 'module'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name_en ?: 'Doctor #'.$d->id,
                'name_en' => $d->name_en,
                'name_ar' => $d->name_ar,
                'specialty' => $d->specialization_en,
                'department' => $d->module,
            ]);

        // Communication templates for quick-send
        $templates = CommunicationTemplate::active()
            ->whereIn('channel', ['whatsapp', 'sms', 'email'])
            ->orderBy('channel')
            ->orderBy('name')
            ->get(['id', 'name', 'channel', 'category', 'subject', 'body_en', 'body_ar', 'variables']);

        // ── Smart Contact Data (best time to call) ──
        $smartContact = $this->computeSmartContactData($lead);

        return Inertia::render('Admin/CRM/Leads/Show', [
            'lead' => $lead,
            'activities' => $activities,
            'followUps' => $followUps,
            'assignees' => $assignees,
            'sources' => $sources,
            'services' => $services,
            'doctors' => $doctors,
            'smartContact' => $smartContact,
            'templates' => $templates,
        ]);
    }

    public function edit(Lead $lead): Response
    {
        $lead->load(['source', 'campaign', 'assignedUser:id,name']);

        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar', 'icon', 'color']);
        $campaigns = CrmCampaign::latest()->get(['id', 'name']);
        $assignees = User::active()->select('id', 'name')->orderBy('name')->get();
        $services = \App\Models\Service::where('status', 'active')->get(['id', 'name_en', 'name_ar']);

        return Inertia::render('Admin/CRM/Leads/Edit', [
            'lead' => $lead,
            'sources' => $sources,
            'campaigns' => $campaigns,
            'assignees' => $assignees,
            'services' => $services,
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'campaign_id' => 'nullable|exists:crm_campaigns,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:1,2,3',
            'status' => 'nullable|in:' . implode(',', Lead::STATUSES),
            'interested_services' => 'nullable|array',
            'notes' => 'nullable|string',
            'loss_reason' => 'nullable|string',
        ]);

        $oldStatus = $lead->status;
        $oldAssignee = $lead->assigned_to;

        // Handle assignment change
        if (isset($data['assigned_to']) && $data['assigned_to'] != $oldAssignee) {
            $data['assigned_at'] = now();
            LeadActivity::logAssignment($lead, $oldAssignee, $data['assigned_to']);
        }

        // Handle status change
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            LeadActivity::logStatusChange($lead, $oldStatus, $data['status']);

            if ($data['status'] === 'lost') {
                $data['lost_at'] = now();
            }
        }

        $lead->update($data);

        return redirect()->route('admin.leads.show', $lead)->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        AuditLogger::log('deleted', $lead);
        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted successfully.');
    }

    /**
     * Log an activity/communication for a lead.
     */
    public function logActivity(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|in:note,call,whatsapp,email,sms,meeting',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'direction' => 'nullable|in:inbound,outbound',
            'duration_seconds' => 'nullable|integer|min:0',
            'outcome' => 'nullable|in:successful,no_answer,busy,voicemail,callback_requested,not_interested',
        ]);

        $data['lead_id'] = $lead->id;
        $data['performed_by'] = auth()->id();

        LeadActivity::create($data);

        // Update last contacted
        $lead->markAsContacted();

        // Apply scoring based on outcome
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

        return back()->with('success', 'Activity logged successfully.');
    }

    /**
     * Schedule a follow-up for a lead.
     */
    public function scheduleFollowUp(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|in:call,whatsapp,email,sms,meeting,other',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $data['lead_id'] = $lead->id;
        $data['assigned_to'] = $data['assigned_to'] ?? $lead->assigned_to ?? auth()->id();
        $data['created_by'] = auth()->id();

        $followUp = LeadFollowUp::create($data);

        // Update lead's next follow-up date
        $lead->update(['next_follow_up_at' => $data['scheduled_at']]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'follow_up_scheduled',
            'subject' => "Follow-up scheduled: {$data['type']} at " . $data['scheduled_at'],
            'performed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Follow-up scheduled successfully.');
    }

    /**
     * Update lead status (quick action).
     */
    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Lead::STATUSES),
            'loss_reason' => 'nullable|required_if:status,lost|string',
        ]);

        $oldStatus = $lead->status;

        $updateData = ['status' => $data['status']];

        if ($data['status'] === 'lost') {
            $updateData['loss_reason'] = $data['loss_reason'] ?? null;
            $updateData['lost_at'] = now();
        }

        // Track first contact time (SLA)
        if ($oldStatus === 'new' && $data['status'] !== 'new' && !$lead->first_contacted_at) {
            $updateData['first_contacted_at'] = now();
        }

        $lead->update($updateData);

        LeadActivity::logStatusChange($lead, $oldStatus, $data['status']);

        // Trigger automation sequences for status change
        \App\Services\FollowUpAutomationService::triggerStatusChange($lead, $data['status']);

        // Cancel all sequences if converted or lost
        if (in_array($data['status'], [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])) {
            \App\Services\FollowUpAutomationService::cancelAllForLead($lead, 'stopped_conversion');
        }

        // Auto-create patient when lead is converted via quick status change
        if ($data['status'] === Lead::STATUS_CONVERTED && ! $lead->patient_id) {
            \App\Events\LeadConverted::dispatch($lead);
        }

        return back()->with('success', 'Lead status updated.');
    }

    /**
     * Convert lead to patient (with optional booking).
     */
    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->status === Lead::STATUS_CONVERTED) {
            return back()->with('error', 'Lead is already converted.');
        }

        // Validate optional booking data
        $data = $request->validate([
            'create_booking' => 'nullable|boolean',
            'booking_type' => 'required_if:create_booking,true|nullable|in:dermatology_consultation,cosmetic_consultation,dental_consultation,service,package_bundle',
            'service_id' => 'nullable|exists:services,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'appointment_date' => 'required_if:create_booking,true|nullable|date|after_or_equal:today',
            'start_time' => 'required_if:create_booking,true|nullable|date_format:H:i',
            'end_time' => 'required_if:create_booking,true|nullable|date_format:H:i|after:start_time',
            'booking_notes' => 'nullable|string|max:1000',
        ]);

        // Create patient from lead data
        $patient = new Patient([
            'full_name' => $lead->full_name,
            'phone' => $lead->phone,
            'phone2' => $lead->phone2,
            'email' => $lead->email,
            'gender' => $lead->gender,
            'date_of_birth' => $lead->date_of_birth,
            'nationality' => $lead->nationality,
            'address' => $lead->city,
            'referral_source' => $lead->source?->slug ?? 'other',
        ]);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        $booking = null;
        $successMessage = "Lead converted to patient #{$patient->file_number} successfully.";

        // Optionally create booking
        if (!empty($data['create_booking'])) {
            $service = $data['service_id'] ? \App\Models\Service::find($data['service_id']) : null;
            $unitPrice = $service?->price ?? 0;

            // For consultation types, use settings-based fees
            if (in_array($data['booking_type'], ['dermatology_consultation', 'cosmetic_consultation'])) {
                $feeKey = $data['booking_type'] === 'dermatology_consultation'
                    ? 'default_dermatology_fee' : 'cosmetic_consultation_fee';
                $unitPrice = (float) \App\Models\Setting::get($feeKey, $unitPrice);
            }

            $bookingData = [
                'patient_id' => $patient->id,
                'full_name' => $patient->full_name,
                'phone' => $patient->phone,
                'source' => 'secretary',
                'booking_type' => $data['booking_type'],
                'notes' => $data['booking_notes'] ?? null,
                'services' => [
                    [
                        'service_id' => $data['service_id'] ?? null,
                        'doctor_id' => $data['doctor_id'] ?? null,
                        'sessions_count' => 1,
                        'unit_price' => $unitPrice,
                        'discount_per_session' => 0,
                        'appointments' => [
                            [
                                'doctor_id' => $data['doctor_id'] ?? null,
                                'date' => $data['appointment_date'],
                                'start_time' => $data['start_time'],
                                'end_time' => $data['end_time'],
                            ],
                        ],
                    ],
                ],
            ];

            $workflowService = app(\App\Services\BookingWorkflowService::class);
            $result = $workflowService->createFromSecretary($bookingData, auth()->id());
            $booking = $result['booking'];

            $successMessage = "Lead converted to patient #{$patient->file_number} with booking #{$booking->booking_number}.";
        }

        $lead->convertToPatient($patient, $booking);

        \App\Events\PatientRegistered::dispatch($patient, 'crm');

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => $booking
                ? "Lead converted to patient #{$patient->file_number} with booking #{$booking->booking_number}"
                : "Lead converted to patient #{$patient->file_number}",
            'metadata' => array_filter([
                'patient_id' => $patient->id,
                'booking_id' => $booking?->id,
            ]),
            'performed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.leads.show', $lead)
            ->with('success', $successMessage);
    }

    /**
     * Bulk actions on leads.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'action' => 'required|in:assign,status,delete',
            'assigned_to' => 'required_if:action,assign|nullable|exists:users,id',
            'status' => 'required_if:action,status|nullable|in:' . implode(',', Lead::STATUSES),
        ]);

        $leads = Lead::whereIn('id', $data['lead_ids'])->get();
        $count = $leads->count();

        switch ($data['action']) {
            case 'assign':
                foreach ($leads as $lead) {
                    $oldAssignee = $lead->assigned_to;
                    $lead->assignTo($data['assigned_to']);
                    LeadActivity::logAssignment($lead, $oldAssignee, $data['assigned_to']);
                }
                return back()->with('success', "{$count} leads assigned successfully.");

            case 'status':
                foreach ($leads as $lead) {
                    $oldStatus = $lead->status;
                    $lead->update(['status' => $data['status']]);
                    LeadActivity::logStatusChange($lead, $oldStatus, $data['status']);
                }
                return back()->with('success', "{$count} leads updated to '{$data['status']}'.");

            case 'delete':
                foreach ($leads as $lead) {
                    AuditLogger::log('deleted', $lead);
                    $lead->delete();
                }
                return back()->with('success', "{$count} leads deleted.");
        }

        return back();
    }

    /**
     * Export leads to CSV.
     */
    public function export(Request $request)
    {
        $query = Lead::with(['source:id,name_en', 'assignedUser:id,name', 'campaign:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($sourceId = $request->input('source_id')) {
            $query->where('lead_source_id', $sourceId);
        }
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $leads = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads-export-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Phone', 'Email', 'Status', 'Priority', 'Score', 'Source', 'Campaign', 'Assigned To', 'City', 'Created At', 'Last Contacted']);

            foreach ($leads as $lead) {
                $priority = match ($lead->priority) {
                    1 => 'Hot', 2 => 'Warm', 3 => 'Cold', default => '-',
                };
                fputcsv($file, [
                    $lead->id,
                    $lead->full_name,
                    $lead->phone,
                    $lead->email,
                    $lead->status,
                    $priority,
                    $lead->score,
                    $lead->source?->name_en ?? '-',
                    $lead->campaign?->name ?? '-',
                    $lead->assignedUser?->name ?? '-',
                    $lead->city ?? '-',
                    $lead->created_at?->format('Y-m-d H:i'),
                    $lead->last_contacted_at?->format('Y-m-d H:i') ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Complete a follow-up.
     */
    public function completeFollowUp(Request $request, LeadFollowUp $followUp): RedirectResponse
    {
        $data = $request->validate([
            'result' => 'nullable|string|max:500',
        ]);

        $followUp->markCompleted($data['result'] ?? null);

        // Log activity
        LeadActivity::create([
            'lead_id' => $followUp->lead_id,
            'type' => 'follow_up_completed',
            'subject' => "Follow-up completed: {$followUp->type}",
            'description' => $data['result'] ?? null,
            'performed_by' => auth()->id(),
        ]);

        // Apply scoring
        LeadScoringRule::applyToLead($followUp->lead, 'follow_up_completed');

        return back()->with('success', 'Follow-up marked as completed.');
    }

    /**
     * Mark a follow-up as missed.
     */
    public function missFollowUp(LeadFollowUp $followUp): RedirectResponse
    {
        $followUp->markMissed();

        LeadActivity::create([
            'lead_id' => $followUp->lead_id,
            'type' => 'system',
            'subject' => "Follow-up missed: {$followUp->type}",
            'performed_by' => auth()->id(),
        ]);

        // Negative scoring
        LeadScoringRule::applyToLead($followUp->lead, 'follow_up_missed');

        // Trigger automation sequences
        \App\Services\FollowUpAutomationService::triggerEvent($followUp->lead, \App\Models\FollowUpSequence::EVENT_FOLLOW_UP_MISSED);

        return back()->with('success', 'Follow-up marked as missed.');
    }

    /**
     * Reschedule a follow-up.
     */
    public function rescheduleFollowUp(Request $request, LeadFollowUp $followUp): RedirectResponse
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $followUp->reschedule(new \DateTime($data['scheduled_at']));

        LeadActivity::create([
            'lead_id' => $followUp->lead_id,
            'type' => 'follow_up_scheduled',
            'subject' => "Follow-up rescheduled to " . $data['scheduled_at'],
            'performed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Follow-up rescheduled.');
    }

    /**
     * Quick-send WhatsApp/SMS/Email using a template.
     */
    public function quickSend(Request $request, Lead $lead): RedirectResponse
    {
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
     * Check for duplicate leads (AJAX).
     */
    public function checkDuplicate(Request $request): \Illuminate\Http\JsonResponse
    {
        $phone = $request->input('phone');
        $email = $request->input('email');

        $duplicates = collect();

        if ($phone) {
            $byPhone = Lead::where('phone', $phone)
                ->orWhere('phone2', $phone)
                ->select('id', 'full_name', 'phone', 'email', 'status', 'created_at')
                ->limit(5)
                ->get();
            $duplicates = $duplicates->merge($byPhone);
        }

        if ($email) {
            $byEmail = Lead::where('email', $email)
                ->select('id', 'full_name', 'phone', 'email', 'status', 'created_at')
                ->limit(5)
                ->get();
            $duplicates = $duplicates->merge($byEmail);
        }

        $duplicates = $duplicates->unique('id')->values();

        return response()->json(['duplicates' => $duplicates]);
    }

    /**
     * Show CSV import page.
     */
    public function importPage(): Response
    {
        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar']);
        $campaigns = CrmCampaign::where('status', 'active')->get(['id', 'name']);
        $assignees = User::active()->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/CRM/Leads/Import', [
            'sources' => $sources,
            'campaigns' => $campaigns,
            'assignees' => $assignees,
        ]);
    }

    /**
     * Process CSV import.
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'campaign_id' => 'nullable|exists:crm_campaigns,id',
            'assigned_to' => 'nullable|exists:users,id',
            'skip_duplicates' => 'boolean',
        ]);

        $file = $request->file('file');
        $skipDuplicates = $request->boolean('skip_duplicates', true);
        $defaultSourceId = $request->input('lead_source_id');
        $defaultCampaignId = $request->input('campaign_id');
        $defaultAssignedTo = $request->input('assigned_to');

        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            return back()->with('error', 'Invalid CSV file. No header row found.');
        }

        // Normalize header names
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        // Map CSV columns to lead fields
        $columnMap = [
            'name' => 'full_name',
            'full_name' => 'full_name',
            'full name' => 'full_name',
            'phone' => 'phone',
            'phone1' => 'phone',
            'mobile' => 'phone',
            'phone2' => 'phone2',
            'email' => 'email',
            'e-mail' => 'email',
            'gender' => 'gender',
            'city' => 'city',
            'nationality' => 'nationality',
            'notes' => 'notes',
            'priority' => 'priority',
        ];

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                $errors++;
                continue;
            }

            $rowData = array_combine($header, $row);
            $leadData = [];

            foreach ($columnMap as $csvCol => $leadField) {
                if (isset($rowData[$csvCol]) && $rowData[$csvCol] !== '') {
                    $leadData[$leadField] = trim($rowData[$csvCol]);
                }
            }

            // Skip if no name
            if (empty($leadData['full_name'])) {
                $errors++;
                continue;
            }

            // Check duplicates
            if ($skipDuplicates) {
                $exists = false;
                if (! empty($leadData['phone'])) {
                    $exists = Lead::where('phone', $leadData['phone'])->exists();
                }
                if (! $exists && ! empty($leadData['email'])) {
                    $exists = Lead::where('email', $leadData['email'])->exists();
                }
                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            // Map priority text to integer
            if (isset($leadData['priority'])) {
                $leadData['priority'] = match (strtolower($leadData['priority'])) {
                    'hot', '1' => 1,
                    'warm', '2' => 2,
                    'cold', '3' => 3,
                    default => 2,
                };
            } else {
                $leadData['priority'] = 2; // Default warm
            }

            // Map gender
            if (isset($leadData['gender'])) {
                $leadData['gender'] = match (strtolower($leadData['gender'])) {
                    'male', 'm' => 'male',
                    'female', 'f' => 'female',
                    default => null,
                };
            }

            $leadData['status'] = 'new';
            $leadData['score'] = 0;
            $leadData['lead_source_id'] = $defaultSourceId;
            $leadData['campaign_id'] = $defaultCampaignId;
            $leadData['assigned_to'] = $defaultAssignedTo;
            $leadData['created_by'] = auth()->id();

            if ($defaultAssignedTo) {
                $leadData['assigned_at'] = now();
            }

            try {
                $lead = Lead::create($leadData);

                // Apply initial scoring
                LeadScoringRule::applyToLead($lead, 'lead_created');
                if ($lead->phone) {
                    LeadScoringRule::applyToLead($lead, 'phone_provided');
                }
                if ($lead->email) {
                    LeadScoringRule::applyToLead($lead, 'email_provided');
                }

                // Auto-assign if not manually assigned
                if (! $lead->assigned_to) {
                    LeadService::autoAssign($lead);
                }

                // Update campaign counter
                if ($lead->campaign_id) {
                    CrmCampaign::where('id', $lead->campaign_id)->increment('leads_count');
                }

                $imported++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        fclose($handle);

        $message = "{$imported} leads imported successfully.";
        if ($skipped > 0) $message .= " {$skipped} duplicates skipped.";
        if ($errors > 0) $message .= " {$errors} rows had errors.";

        return redirect()->route('admin.leads.index')->with('success', $message);
    }

    /**
     * Show merge confirmation page for two leads.
     */
    public function mergePage(Request $request): Response
    {
        $request->validate([
            'primary_id' => 'required|exists:leads,id',
            'secondary_id' => 'required|exists:leads,id|different:primary_id',
        ]);

        $primary = Lead::with(['source', 'assignedUser', 'activities', 'followUps', 'campaign'])->findOrFail($request->primary_id);
        $secondary = Lead::with(['source', 'assignedUser', 'activities', 'followUps', 'campaign'])->findOrFail($request->secondary_id);

        return Inertia::render('Admin/CRM/Leads/Merge', [
            'primary' => $primary,
            'secondary' => $secondary,
        ]);
    }

    /**
     * Merge two leads: keep the primary, absorb data from secondary.
     */
    public function merge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary_id' => 'required|exists:leads,id',
            'secondary_id' => 'required|exists:leads,id|different:primary_id',
            'fields' => 'required|array', // which fields to keep from secondary
        ]);

        $primary = Lead::findOrFail($data['primary_id']);
        $secondary = Lead::findOrFail($data['secondary_id']);

        // Merge selected fields from secondary into primary
        $mergeableFields = [
            'phone', 'phone2', 'email', 'gender', 'age', 'city', 'nationality',
            'notes', 'priority', 'lead_source_id', 'campaign_id', 'assigned_to',
            'interested_services', 'utm_source', 'utm_medium', 'utm_campaign',
        ];

        $fieldsToMerge = [];
        foreach ($data['fields'] as $field) {
            if (in_array($field, $mergeableFields) && $secondary->$field) {
                $fieldsToMerge[$field] = $secondary->$field;
            }
        }

        if (! empty($fieldsToMerge)) {
            $primary->update($fieldsToMerge);
        }

        // Take the higher score
        if ($secondary->score > $primary->score) {
            $primary->update(['score' => $secondary->score]);
        }

        // Move all activities from secondary to primary
        LeadActivity::where('lead_id', $secondary->id)->update(['lead_id' => $primary->id]);

        // Move all follow-ups from secondary to primary
        LeadFollowUp::where('lead_id', $secondary->id)->update(['lead_id' => $primary->id]);

        // Move linked bookings
        \App\Models\Booking::where('lead_id', $secondary->id)->update(['lead_id' => $primary->id]);

        // Move linked contact messages
        \App\Models\ContactMessage::where('lead_id', $secondary->id)->update(['lead_id' => $primary->id]);

        // Log the merge as activity
        LeadActivity::create([
            'lead_id' => $primary->id,
            'type' => 'system',
            'subject' => 'Lead merged',
            'description' => "Merged with lead #{$secondary->id} ({$secondary->full_name}). Data absorbed into this lead.",
            'performed_by' => auth()->id(),
            'metadata' => [
                'merged_lead_id' => $secondary->id,
                'merged_lead_name' => $secondary->full_name,
                'fields_merged' => array_keys($fieldsToMerge),
            ],
        ]);

        AuditLogger::log('lead_merged', $primary, [
            'primary_lead' => $primary->full_name,
            'secondary_lead' => $secondary->full_name,
            'secondary_lead_id' => $secondary->id,
        ]);

        // Soft-delete the secondary lead
        $secondary->update(['status' => Lead::STATUS_LOST, 'loss_reason' => 'Merged into lead #' . $primary->id]);
        $secondary->delete();

        return redirect("/admin/leads/{$primary->id}")->with('success', "Leads merged successfully. Lead #{$secondary->id} has been archived.");
    }

    /**
     * Pipeline/Kanban board view.
     */
    public function pipeline(Request $request): Response
    {
        $columns = [];
        foreach (Lead::PIPELINE_STATUSES as $status) {
            $columns[$status] = Lead::with(['source:id,name_en,icon,color', 'assignedUser:id,name'])
                ->where('status', $status)
                ->latest()
                ->limit(50)
                ->get();
        }

        $assignees = User::active()->select('id', 'name')->orderBy('name')->get();
        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar', 'icon', 'color']);

        return Inertia::render('Admin/CRM/Leads/Pipeline', [
            'columns' => $columns,
            'assignees' => $assignees,
            'sources' => $sources,
        ]);
    }

    /**
     * Quick view lead details (JSON for pipeline drawer).
     */
    public function quickView(Lead $lead): \Illuminate\Http\JsonResponse
    {
        $lead->load([
            'source:id,name_en,icon,color',
            'campaign:id,name',
            'assignedUser:id,name',
            'patient:id,full_name,file_number,phone',
            'creator:id,name',
        ]);

        $activities = LeadActivity::where('lead_id', $lead->id)
            ->with('performer:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $followUps = LeadFollowUp::where('lead_id', $lead->id)
            ->where('status', 'pending')
            ->with(['assignedUser:id,name'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return response()->json([
            'lead' => $lead,
            'activities' => $activities,
            'followUps' => $followUps,
        ]);
    }

    /**
     * Compute smart contact insights from activity history.
     */
    private function computeSmartContactData(Lead $lead): array
    {
        // Get all communication activities for this lead (call, whatsapp, email, sms)
        $comms = LeadActivity::where('lead_id', $lead->id)
            ->whereIn('type', ['call', 'whatsapp', 'email', 'sms'])
            ->select('type', 'outcome', 'direction', 'created_at')
            ->orderBy('created_at')
            ->get();

        if ($comms->isEmpty()) {
            return [
                'best_hours' => [],
                'successful_hours' => [],
                'best_day' => null,
                'total_attempts' => 0,
                'successful_attempts' => 0,
                'last_successful_at' => null,
                'preferred_channel' => null,
                'avg_response_gap_hours' => null,
            ];
        }

        // Analyze successful contacts by hour
        $hourBuckets = array_fill(0, 24, ['total' => 0, 'successful' => 0]);
        $dayBuckets = array_fill(0, 7, ['total' => 0, 'successful' => 0]); // 0=Sun..6=Sat
        $channelSuccess = [];
        $lastSuccessful = null;

        foreach ($comms as $comm) {
            $hour = (int) $comm->created_at->format('G'); // 0-23
            $day = (int) $comm->created_at->format('w');   // 0=Sun..6=Sat

            $hourBuckets[$hour]['total']++;
            $dayBuckets[$day]['total']++;

            $isSuccess = $comm->outcome === 'successful'
                || ($comm->type !== 'call' && $comm->direction === 'inbound');

            if ($isSuccess) {
                $hourBuckets[$hour]['successful']++;
                $dayBuckets[$day]['successful']++;
                $channelSuccess[$comm->type] = ($channelSuccess[$comm->type] ?? 0) + 1;
                $lastSuccessful = $comm->created_at->toIso8601String();
            }
        }

        // Find top 3 hours with most successful contacts
        $successfulHours = collect($hourBuckets)
            ->map(fn ($data, $hour) => ['hour' => $hour, 'successful' => $data['successful'], 'total' => $data['total']])
            ->filter(fn ($h) => $h['successful'] > 0)
            ->sortByDesc('successful')
            ->take(3)
            ->values()
            ->map(fn ($h) => [
                'hour' => $h['hour'],
                'label' => Carbon::createFromTime($h['hour'])->format('g A'),
                'successful' => $h['successful'],
                'total' => $h['total'],
            ])
            ->toArray();

        // Find best day
        $bestDay = collect($dayBuckets)
            ->map(fn ($data, $day) => ['day' => $day, 'successful' => $data['successful'], 'total' => $data['total']])
            ->sortByDesc('successful')
            ->first();

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Preferred channel
        $preferredChannel = !empty($channelSuccess)
            ? array_search(max($channelSuccess), $channelSuccess)
            : ($comms->countBy('type')->sortDesc()->keys()->first());

        // Average gap between responses (for inbound messages)
        $inbounds = $comms->filter(fn ($c) => $c->direction === 'inbound')->values();
        $avgGap = null;
        if ($inbounds->count() >= 2) {
            $gaps = [];
            for ($i = 1; $i < $inbounds->count(); $i++) {
                $gaps[] = $inbounds[$i]->created_at->diffInHours($inbounds[$i - 1]->created_at);
            }
            $avgGap = round(array_sum($gaps) / count($gaps), 1);
        }

        return [
            'best_hours' => $successfulHours,
            'best_day' => $bestDay && $bestDay['successful'] > 0
                ? ['name' => $dayNames[$bestDay['day']], 'successful' => $bestDay['successful']]
                : null,
            'total_attempts' => $comms->count(),
            'successful_attempts' => $comms->where('outcome', 'successful')->count()
                + $comms->where('direction', 'inbound')->count(),
            'last_successful_at' => $lastSuccessful,
            'preferred_channel' => $preferredChannel,
            'avg_response_gap_hours' => $avgGap,
        ];
    }
}

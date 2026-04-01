<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationTemplate;
use App\Models\FollowUpSequence;
use App\Models\FollowUpSequenceStep;
use App\Models\Lead;
use App\Models\LeadSequenceEnrollment;
use App\Models\LeadSource;
use App\Models\CrmCampaign;
use App\Services\AuditLogger;
use App\Services\FollowUpAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpSequenceController extends Controller
{
    public function index(Request $request): Response
    {
        $sequences = FollowUpSequence::with(['steps', 'creator'])
            ->withCount(['enrollments as active_enrollments_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount('enrollments as total_enrollments_count')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status !== null && $request->status !== '', function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/CRM/Sequences/Index', [
            'sequences' => $sequences,
            'filters' => $request->only(['search', 'status']),
            'triggerEvents' => FollowUpSequence::TRIGGER_EVENTS,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CRM/Sequences/Create', [
            'triggerEvents' => FollowUpSequence::TRIGGER_EVENTS,
            'actionTypes' => FollowUpSequenceStep::ACTION_TYPES,
            'leadStatuses' => [
                Lead::STATUS_NEW => 'New',
                Lead::STATUS_CONTACTED => 'Contacted',
                Lead::STATUS_QUALIFIED => 'Qualified',
                Lead::STATUS_APPOINTMENT_BOOKED => 'Appointment Booked',
                Lead::STATUS_CONSULTATION_DONE => 'Consultation Done',
                Lead::STATUS_NEGOTIATION => 'Negotiation',
                Lead::STATUS_DORMANT => 'Dormant',
            ],
            'sources' => LeadSource::where('is_active', true)->orderBy('sort_order')->get(),
            'campaigns' => CrmCampaign::active()->get(),
            'templates' => CommunicationTemplate::active()->get(['id', 'name', 'channel', 'category']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_event' => 'required|string',
            'trigger_conditions' => 'nullable|array',
            'trigger_conditions.lead_source_id' => 'nullable|integer',
            'trigger_conditions.priority' => 'nullable|integer|in:1,2,3',
            'trigger_conditions.campaign_id' => 'nullable|integer',
            'is_active' => 'boolean',
            'stop_on_reply' => 'boolean',
            'stop_on_conversion' => 'boolean',
            'max_enrollments' => 'nullable|integer|min:1',
            'steps' => 'required|array|min:1',
            'steps.*.step_order' => 'required|integer|min:1',
            'steps.*.delay_minutes' => 'required|integer|min:0',
            'steps.*.action_type' => 'required|string',
            'steps.*.template_id' => 'nullable|integer|exists:communication_templates,id',
            'steps.*.follow_up_type' => 'nullable|string|in:call,whatsapp,email,sms,meeting',
            'steps.*.target_status' => 'nullable|string',
            'steps.*.score_points' => 'nullable|integer|min:-100|max:100',
            'steps.*.message_en' => 'nullable|string|max:1000',
            'steps.*.message_ar' => 'nullable|string|max:1000',
            'steps.*.notification_message' => 'nullable|string|max:500',
            'steps.*.is_active' => 'boolean',
        ]);

        $sequence = FollowUpSequence::create([
            ...\Illuminate\Support\Arr::except($validated, ['steps']),
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['steps'] as $stepData) {
            $sequence->steps()->create($stepData);
        }

        AuditLogger::log('created', $sequence, ['name' => $sequence->name]);

        return redirect()->route('admin.sequences.index')
            ->with('success', 'Automation sequence created successfully.');
    }

    public function edit(FollowUpSequence $sequence): Response
    {
        $sequence->load('steps');

        return Inertia::render('Admin/CRM/Sequences/Edit', [
            'sequence' => $sequence,
            'triggerEvents' => FollowUpSequence::TRIGGER_EVENTS,
            'actionTypes' => FollowUpSequenceStep::ACTION_TYPES,
            'leadStatuses' => [
                Lead::STATUS_NEW => 'New',
                Lead::STATUS_CONTACTED => 'Contacted',
                Lead::STATUS_QUALIFIED => 'Qualified',
                Lead::STATUS_APPOINTMENT_BOOKED => 'Appointment Booked',
                Lead::STATUS_CONSULTATION_DONE => 'Consultation Done',
                Lead::STATUS_NEGOTIATION => 'Negotiation',
                Lead::STATUS_DORMANT => 'Dormant',
            ],
            'sources' => LeadSource::where('is_active', true)->orderBy('sort_order')->get(),
            'campaigns' => CrmCampaign::active()->get(),
            'templates' => CommunicationTemplate::active()->get(['id', 'name', 'channel', 'category']),
        ]);
    }

    public function update(Request $request, FollowUpSequence $sequence): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_event' => 'required|string',
            'trigger_conditions' => 'nullable|array',
            'trigger_conditions.lead_source_id' => 'nullable|integer',
            'trigger_conditions.priority' => 'nullable|integer|in:1,2,3',
            'trigger_conditions.campaign_id' => 'nullable|integer',
            'is_active' => 'boolean',
            'stop_on_reply' => 'boolean',
            'stop_on_conversion' => 'boolean',
            'max_enrollments' => 'nullable|integer|min:1',
            'steps' => 'required|array|min:1',
            'steps.*.id' => 'nullable|integer',
            'steps.*.step_order' => 'required|integer|min:1',
            'steps.*.delay_minutes' => 'required|integer|min:0',
            'steps.*.action_type' => 'required|string',
            'steps.*.template_id' => 'nullable|integer|exists:communication_templates,id',
            'steps.*.follow_up_type' => 'nullable|string|in:call,whatsapp,email,sms,meeting',
            'steps.*.target_status' => 'nullable|string',
            'steps.*.score_points' => 'nullable|integer|min:-100|max:100',
            'steps.*.message_en' => 'nullable|string|max:1000',
            'steps.*.message_ar' => 'nullable|string|max:1000',
            'steps.*.notification_message' => 'nullable|string|max:500',
            'steps.*.is_active' => 'boolean',
        ]);

        $sequence->update(\Illuminate\Support\Arr::except($validated, ['steps']));

        // Sync steps: delete removed, update existing, create new
        $existingIds = collect($validated['steps'])->pluck('id')->filter()->toArray();
        $sequence->steps()->whereNotIn('id', $existingIds)->delete();

        foreach ($validated['steps'] as $stepData) {
            if (isset($stepData['id']) && $stepData['id']) {
                $sequence->steps()->where('id', $stepData['id'])->update(
                    \Illuminate\Support\Arr::except($stepData, ['id'])
                );
            } else {
                $sequence->steps()->create(\Illuminate\Support\Arr::except($stepData, ['id']));
            }
        }

        AuditLogger::log('updated', $sequence, ['name' => $sequence->name]);

        return redirect()->route('admin.sequences.index')
            ->with('success', 'Automation sequence updated successfully.');
    }

    public function destroy(FollowUpSequence $sequence): RedirectResponse
    {
        // Cancel all active enrollments first
        LeadSequenceEnrollment::where('sequence_id', $sequence->id)
            ->active()
            ->update([
                'status' => LeadSequenceEnrollment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'next_step_at' => null,
            ]);

        AuditLogger::log('deleted', $sequence, ['name' => $sequence->name]);
        $sequence->delete();

        return redirect()->route('admin.sequences.index')
            ->with('success', 'Automation sequence deleted.');
    }

    /**
     * Toggle sequence active status.
     */
    public function toggle(FollowUpSequence $sequence): RedirectResponse
    {
        $sequence->update(['is_active' => ! $sequence->is_active]);

        // If deactivated, pause all active enrollments
        if (! $sequence->is_active) {
            LeadSequenceEnrollment::where('sequence_id', $sequence->id)
                ->active()
                ->update(['status' => LeadSequenceEnrollment::STATUS_PAUSED]);
        }

        return redirect()->back()->with('success',
            $sequence->is_active ? 'Sequence activated.' : 'Sequence paused.'
        );
    }

    /**
     * View enrollments for a specific sequence.
     */
    public function enrollments(Request $request, FollowUpSequence $sequence): Response
    {
        $enrollments = LeadSequenceEnrollment::with(['lead'])
            ->where('sequence_id', $sequence->id)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('enrolled_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/CRM/Sequences/Enrollments', [
            'sequence' => $sequence,
            'enrollments' => $enrollments,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Manually enroll a lead into a sequence.
     */
    public function enrollLead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'sequence_id' => 'required|exists:follow_up_sequences,id',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);
        $sequence = FollowUpSequence::findOrFail($validated['sequence_id']);

        $enrollment = FollowUpAutomationService::enrollLead($lead, $sequence);

        if (! $enrollment) {
            return redirect()->back()->with('error', 'Could not enroll lead. Already enrolled or conditions not met.');
        }

        return redirect()->back()->with('success', "Lead enrolled in sequence: {$sequence->name}");
    }

    /**
     * Cancel an enrollment.
     */
    public function cancelEnrollment(LeadSequenceEnrollment $enrollment): RedirectResponse
    {
        $enrollment->cancel();

        return redirect()->back()->with('success', 'Enrollment cancelled.');
    }
}

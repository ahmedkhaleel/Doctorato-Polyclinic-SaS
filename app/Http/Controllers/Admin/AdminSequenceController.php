<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationEvent;
use App\Models\NotificationSequence;
use App\Models\Patient;
use App\Services\Notifications\SequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSequenceController extends Controller
{
    public function __construct(private SequenceService $sequences) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Notifications/Sequences', [
            'sequences' => NotificationSequence::with('steps')->withCount([
                'enrollments as active_count' => fn ($q) => $q->where('status', 'active'),
                'enrollments as completed_count' => fn ($q) => $q->where('status', 'completed'),
            ])->latest()->get(),
            'events' => NotificationEvent::orderBy('key')->get(['key', 'label_ar', 'label_en']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSequence($request);
        $sequence = NotificationSequence::create([
            'name' => $data['name'],
            'trigger_event' => $data['trigger_event'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
        $this->syncSteps($sequence, $data['steps'] ?? []);

        return back()->with('success', __('Sequence saved.'));
    }

    public function update(Request $request, NotificationSequence $sequence): RedirectResponse
    {
        $data = $this->validateSequence($request);
        $sequence->update([
            'name' => $data['name'],
            'trigger_event' => $data['trigger_event'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
        $sequence->steps()->delete();
        $this->syncSteps($sequence, $data['steps'] ?? []);

        return back()->with('success', __('Sequence updated.'));
    }

    public function destroy(NotificationSequence $sequence): RedirectResponse
    {
        $sequence->delete(); // cascades steps + enrollments

        return back()->with('success', __('Sequence deleted.'));
    }

    /** Manually enrol a patient. */
    public function enroll(Request $request, NotificationSequence $sequence): RedirectResponse
    {
        $data = $request->validate(['patient_id' => 'required|integer|exists:patients,id']);
        $patient = Patient::find($data['patient_id']);

        $enrollment = $this->sequences->enroll($sequence, $patient);

        return back()->with($enrollment ? 'success' : 'error',
            $enrollment ? __('Patient enrolled.') : __('Already enrolled or sequence empty.'));
    }

    private function validateSequence(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'trigger_event' => 'nullable|string|exists:notification_events,key',
            'is_active' => 'sometimes|boolean',
            'steps' => 'nullable|array',
            'steps.*.delay_minutes' => 'required|integer|min:0',
            'steps.*.channel' => 'nullable|in:whatsapp,sms,email,in_app',
            'steps.*.subject' => 'nullable|string|max:255',
            'steps.*.body_ar' => 'required|string|max:2000',
            'steps.*.body_en' => 'nullable|string|max:2000',
        ]);
    }

    private function syncSteps(NotificationSequence $sequence, array $steps): void
    {
        foreach (array_values($steps) as $i => $step) {
            $sequence->steps()->create([
                'position' => $i,
                'delay_minutes' => (int) $step['delay_minutes'],
                'channel' => $step['channel'] ?? null,
                'subject' => $step['subject'] ?? null,
                'body_ar' => $step['body_ar'],
                'body_en' => $step['body_en'] ?? null,
            ]);
        }
    }
}

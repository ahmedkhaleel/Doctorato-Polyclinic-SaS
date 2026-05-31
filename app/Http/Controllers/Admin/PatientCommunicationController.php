<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Services\Notifications\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The "Communications" tab of a patient file: full delivery history across all
 * channels, manual send, and per-channel/category consent toggles.
 */
class PatientCommunicationController extends Controller
{
    public function index(Patient $patient): Response
    {
        $logs = NotificationLog::where('recipient_type', $patient->getMorphClass())
            ->where('recipient_id', $patient->getKey())
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'channel' => $l->channel,
                'event_key' => $l->event_key,
                'status' => $l->status,
                'to' => $l->to,
                'error' => $l->error,
                'cost' => $l->cost,
                'direction' => $l->meta['direction'] ?? 'outbound',
                'body' => $l->meta['body'] ?? null,
                'created_at' => $l->created_at?->toIso8601String(),
                'delivered_at' => $l->delivered_at?->toIso8601String(),
                'read_at' => $l->read_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Patients/Communications', [
            'patient' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
                'file_number' => $patient->file_number,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'preferred_language' => $patient->preferred_language ?? 'ar',
            ],
            'preferences' => $this->preferences($patient),
            'logs' => $logs,
            'channelKeys' => ['whatsapp', 'sms', 'email', 'in_app'],
        ]);
    }

    /** Manual free-form send from the patient file (permission: notifications.send). */
    public function send(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'channel' => 'required|in:whatsapp,sms,email,in_app',
            'body' => 'required|string|max:2000',
            'subject' => 'nullable|string|max:255',
        ]);

        // Manual sends are staff-initiated and intentional → bypass routing/consent
        // by forcing the chosen channel on a transactional event.
        $logs = Notifier::eventNow('account.created', $patient, [
            'body' => $data['body'],
            'subject' => $data['subject'] ?? null,
            'meta' => ['manual' => true, 'body' => $data['body']],
        ], [$data['channel']]);

        $log = collect($logs)->first();
        $ok = $log && in_array($log->status, [NotificationLog::STATUS_SENT, NotificationLog::STATUS_DELIVERED], true);

        return back()->with($ok ? 'success' : 'error',
            $ok ? __('Message sent.') : __('Send failed: ').($log->error ?? __('channel not ready')));
    }

    public function updatePreferences(Request $request, Patient $patient): RedirectResponse
    {
        $cols = [];
        foreach (['email', 'sms', 'whatsapp'] as $ch) {
            foreach (['bookings', 'reminders', 'marketing'] as $cat) {
                $cols["notify_{$ch}_{$cat}"] = 'boolean';
            }
        }
        $cols['preferred_language'] = 'in:ar,en';

        $validated = $request->validate(array_map(fn ($r) => "sometimes|{$r}", $cols));

        // notify_* are not in $fillable (consent is sensitive) → forceFill.
        $patient->forceFill($validated)->save();

        return back()->with('success', __('Preferences updated.'));
    }

    private function preferences(Patient $patient): array
    {
        $out = [];
        foreach (['email', 'sms', 'whatsapp'] as $ch) {
            foreach (['bookings', 'reminders', 'marketing'] as $cat) {
                $out["notify_{$ch}_{$cat}"] = (bool) $patient->wantsNotification($cat, $ch);
            }
        }

        return $out;
    }
}

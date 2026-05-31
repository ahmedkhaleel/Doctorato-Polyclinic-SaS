<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Services\Notifications\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Two-way messaging inbox built on notification_logs (whatsapp/sms). A
 * "conversation" is all messages sharing one contact phone; inbound messages
 * carry an `inbound.` event_key prefix. Shared by admin + secretary panels.
 */
class NotificationInboxController extends Controller
{
    private const MSG_CHANNELS = ['whatsapp', 'sms'];

    public function index(Request $request, string $panel = 'admin'): Response
    {
        $conversations = NotificationLog::query()
            ->whereIn('channel', self::MSG_CHANNELS)
            ->whereNotNull('to')
            ->where('to', '!=', '')
            ->select('to',
                DB::raw('MAX(created_at) as last_at'),
                DB::raw('COUNT(*) as messages'),
                DB::raw("SUM(CASE WHEN event_key LIKE 'inbound.%' AND read_at IS NULL THEN 1 ELSE 0 END) as unread"))
            ->groupBy('to')
            ->orderByDesc('last_at')
            ->paginate(25)
            ->through(fn ($row) => [
                'contact' => $row->to,
                'last_at' => $row->last_at,
                'messages' => (int) $row->messages,
                'unread' => (int) $row->unread,
                'patient' => $this->matchPatient($row->to),
            ]);

        return Inertia::render('Shared/NotificationInbox', [
            'panel' => $panel,
            'conversations' => $conversations,
            'active' => $request->filled('contact') ? $this->thread($request->input('contact')) : null,
        ]);
    }

    public function show(Request $request, string $panel = 'admin'): Response
    {
        $contact = (string) $request->input('contact');

        // Opening a conversation marks its inbound messages read.
        if ($contact !== '') {
            NotificationLog::whereIn('channel', self::MSG_CHANNELS)
                ->where('to', $contact)->where('event_key', 'like', 'inbound.%')->whereNull('read_at')
                ->update(['read_at' => now(), 'status' => NotificationLog::STATUS_READ]);
        }

        return $this->index($request, $panel);
    }

    public function reply(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact' => 'required|string|max:32',
            'body' => 'required|string|max:2000',
            'channel' => 'nullable|in:whatsapp,sms',
        ]);

        $channel = $data['channel'] ?? $this->defaultChannel();
        $patient = $this->matchPatientModel($data['contact']);

        Notifier::eventNow('manual.message', $patient, [
            'to' => $data['contact'],
            'body' => $data['body'],
            'meta' => ['direction' => 'outbound', 'body' => $data['body'], 'reply' => true],
        ], [$channel]);

        return back()->with('success', __('Reply sent.'));
    }

    /** Build the message list for one conversation. */
    private function thread(string $contact): array
    {
        $messages = NotificationLog::whereIn('channel', self::MSG_CHANNELS)
            ->where('to', $contact)
            ->orderBy('created_at')
            ->limit(300)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'channel' => $l->channel,
                'direction' => str_starts_with((string) $l->event_key, 'inbound.') ? 'inbound' : 'outbound',
                'body' => $l->meta['body'] ?? '',
                'status' => $l->status,
                'created_at' => $l->created_at?->toIso8601String(),
            ]);

        return [
            'contact' => $contact,
            'patient' => $this->matchPatient($contact),
            'messages' => $messages,
        ];
    }

    private function defaultChannel(): string
    {
        $wa = NotificationChannel::for('whatsapp');

        return ($wa && $wa->enabled) ? 'whatsapp' : 'sms';
    }

    private function matchPatientModel(string $contact): ?Patient
    {
        $tail = substr(preg_replace('/[^0-9]/', '', $contact), -9);
        if (! $tail) {
            return null;
        }

        return Patient::whereRaw("REPLACE(REPLACE(REPLACE(phone,'+',''),' ',''),'-','') LIKE ?", ["%{$tail}"])->first();
    }

    private function matchPatient(string $contact): ?array
    {
        $p = $this->matchPatientModel($contact);

        return $p ? ['id' => $p->id, 'name' => $p->full_name, 'file_number' => $p->file_number] : null;
    }
}

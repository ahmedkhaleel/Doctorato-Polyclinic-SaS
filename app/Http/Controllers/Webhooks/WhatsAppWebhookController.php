<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives WhatsApp delivery/read receipts (and the Meta verification handshake).
 *
 *   GET  /webhooks/whatsapp  → Meta subscription verification (echo hub.challenge)
 *   POST /webhooks/whatsapp  → status receipts → update notification_logs
 *
 * Supports both the Meta Cloud API payload shape and a simple bridge shape
 * ({ id, status }). The route is CSRF-exempt (see bootstrap/app.php).
 */
class WhatsAppWebhookController extends Controller
{
    /** Meta calls this once to verify the callback URL. */
    public function verify(Request $request)
    {
        $verifyToken = Setting::get('whatsapp_webhook_verify_token', '');

        if ($request->query('hub_mode') === 'subscribe'
            && $verifyToken !== ''
            && hash_equals((string) $verifyToken, (string) $request->query('hub_verify_token'))) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        $payload = $request->all();

        // Meta Cloud API shape: entry[].changes[].value.statuses[]
        $statuses = data_get($payload, 'entry.*.changes.*.value.statuses.*', []);
        if (! empty($statuses)) {
            foreach ($statuses as $status) {
                $this->applyStatus($status['id'] ?? null, $status['status'] ?? null, $status['errors'][0]['title'] ?? null);
            }

            // Capture inbound messages so the patient conversation (P6) can use them.
            $messages = data_get($payload, 'entry.*.changes.*.value.messages.*', []);
            foreach ($messages as $msg) {
                $this->recordInbound($msg);
            }

            return response()->json(['received' => true]);
        }

        // Simple bridge shape: { id, status } or { messageId, status }.
        if ($request->filled('status') && ($request->filled('id') || $request->filled('messageId'))) {
            $this->applyStatus($request->input('id', $request->input('messageId')), $request->input('status'), $request->input('error'));
        }

        return response()->json(['received' => true]);
    }

    /** Update the matching log row's status from a provider receipt. */
    private function applyStatus(?string $providerRef, ?string $status, ?string $error = null): void
    {
        if (! $providerRef || ! $status) {
            return;
        }

        $log = NotificationLog::where('provider_ref', $providerRef)->where('channel', 'whatsapp')->first();
        if (! $log) {
            return;
        }

        $map = [
            'sent' => NotificationLog::STATUS_SENT,
            'delivered' => NotificationLog::STATUS_DELIVERED,
            'read' => NotificationLog::STATUS_READ,
            'failed' => NotificationLog::STATUS_FAILED,
        ];
        $mapped = $map[strtolower($status)] ?? null;
        if (! $mapped) {
            return;
        }

        // Never downgrade a more-advanced status (read → delivered).
        $rank = [
            NotificationLog::STATUS_QUEUED => 0,
            NotificationLog::STATUS_SENT => 1,
            NotificationLog::STATUS_DELIVERED => 2,
            NotificationLog::STATUS_READ => 3,
            NotificationLog::STATUS_FAILED => 1,
        ];
        if (($rank[$mapped] ?? 0) < ($rank[$log->status] ?? 0)) {
            return;
        }

        $update = ['status' => $mapped];
        if ($mapped === NotificationLog::STATUS_DELIVERED && ! $log->delivered_at) {
            $update['delivered_at'] = now();
        }
        if ($mapped === NotificationLog::STATUS_READ && ! $log->read_at) {
            $update['read_at'] = now();
            $update['delivered_at'] = $log->delivered_at ?? now();
        }
        if ($mapped === NotificationLog::STATUS_FAILED) {
            $update['error'] = $error ?: 'Delivery failed (provider receipt).';
        }

        $log->update($update);
    }

    /** Log an inbound WhatsApp message as an in_app record for the conversation view. */
    private function recordInbound(array $msg): void
    {
        $from = $msg['from'] ?? null;
        $body = $msg['text']['body'] ?? ($msg['type'] ?? 'message');
        if (! $from) {
            return;
        }

        NotificationLog::create([
            'to' => $from,
            'channel' => 'whatsapp',
            'provider' => optional(NotificationChannel::for('whatsapp'))->provider ?? 'cloud_api',
            'provider_ref' => $msg['id'] ?? null,
            'event_key' => 'inbound.whatsapp',
            'status' => NotificationLog::STATUS_DELIVERED,
            'meta' => ['direction' => 'inbound', 'body' => $body, 'wa_type' => $msg['type'] ?? 'text'],
            'delivered_at' => now(),
        ]);

        Log::info('Inbound WhatsApp message recorded', ['from' => $from]);
    }
}

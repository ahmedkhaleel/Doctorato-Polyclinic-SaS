<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
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

    /** Update the matching log row's status from a Meta status receipt. */
    private function applyStatus(?string $providerRef, ?string $status, ?string $error = null): void
    {
        if (! $providerRef || ! $status) {
            return;
        }

        $mapped = [
            'sent' => NotificationLog::STATUS_SENT,
            'delivered' => NotificationLog::STATUS_DELIVERED,
            'read' => NotificationLog::STATUS_READ,
            'failed' => NotificationLog::STATUS_FAILED,
        ][strtolower($status)] ?? null;

        if ($mapped) {
            app(\App\Services\Notifications\DeliveryReceiptService::class)->apply($providerRef, 'whatsapp', $mapped, $error);
        }
    }

    /** Log an inbound WhatsApp message (conversation entry + STOP handling). */
    private function recordInbound(array $msg): void
    {
        $from = $msg['from'] ?? null;
        $body = $msg['text']['body'] ?? ($msg['type'] ?? 'message');
        if (! $from) {
            return;
        }

        app(\App\Services\Notifications\InboundMessageService::class)
            ->record('whatsapp', $from, $body, $msg['id'] ?? null);
    }
}

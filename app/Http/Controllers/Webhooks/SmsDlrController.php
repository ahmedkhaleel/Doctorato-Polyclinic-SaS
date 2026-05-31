<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Services\Notifications\DeliveryReceiptService;
use App\Services\Notifications\InboundMessageService;
use Illuminate\Http\Request;

/**
 * SMS delivery-receipt (DLR) callbacks. Twilio POSTs MessageSid + MessageStatus;
 * SMS Misr posts its own DLR shape. Both update notification_logs by provider_ref
 * via the shared monotonic DeliveryReceiptService. CSRF-exempt (see bootstrap/app.php).
 */
class SmsDlrController extends Controller
{
    public function __construct(private DeliveryReceiptService $receipts) {}

    /** Twilio StatusCallback: queued|sent|delivered|undelivered|failed|read. */
    public function twilio(Request $request)
    {
        $sid = $request->input('MessageSid', $request->input('SmsSid'));
        $status = strtolower((string) $request->input('MessageStatus', $request->input('SmsStatus')));

        $mapped = [
            'sent' => NotificationLog::STATUS_SENT,
            'delivered' => NotificationLog::STATUS_DELIVERED,
            'read' => NotificationLog::STATUS_READ,
            'undelivered' => NotificationLog::STATUS_FAILED,
            'failed' => NotificationLog::STATUS_FAILED,
        ][$status] ?? null;

        if ($sid && $mapped) {
            $this->receipts->apply($sid, 'sms', $mapped, $request->input('ErrorCode') ? "Twilio error {$request->input('ErrorCode')}" : null);
        }

        return response()->json(['received' => true]);
    }

    /** SMS Misr DLR: flexible field names; "DELIVRD"/2 = delivered, else failed. */
    public function smsmisr(Request $request)
    {
        $ref = (string) $request->input('SMSID', $request->input('MessageID', $request->input('id', '')));
        $statusRaw = strtoupper((string) $request->input('DLRStatus', $request->input('Status', $request->input('status', ''))));

        $delivered = in_array($statusRaw, ['DELIVRD', 'DELIVERED', '1', '2'], true);
        $failed = in_array($statusRaw, ['UNDELIV', 'FAILED', 'EXPIRED', 'REJECTD', '3', '4', '5'], true);

        $mapped = $delivered ? NotificationLog::STATUS_DELIVERED : ($failed ? NotificationLog::STATUS_FAILED : null);

        if ($ref !== '' && $mapped) {
            $this->receipts->apply($ref, 'sms', $mapped, $failed ? "SMS Misr DLR: {$statusRaw}" : null);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Inbound SMS (Twilio: From/Body/MessageSid; SMS Misr: Mobile/Message).
     * Records a conversation entry + runs STOP handling. Returns empty TwiML so
     * Twilio doesn't auto-reply.
     */
    public function inbound(Request $request, InboundMessageService $inbound)
    {
        $from = (string) $request->input('From', $request->input('Mobile', $request->input('msisdn', '')));
        $body = (string) $request->input('Body', $request->input('Message', $request->input('text', '')));
        $ref = $request->input('MessageSid', $request->input('SMSID'));

        if ($from !== '' && $body !== '') {
            $inbound->record('sms', $from, $body, $ref);
        }

        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
            ->header('Content-Type', 'text/xml');
    }
}

<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;

/**
 * Records an inbound message (WhatsApp or SMS) as a conversation entry and
 * handles STOP/unsubscribe keywords. Shared by the WhatsApp + SMS webhooks so
 * two-way messaging and opt-out behave identically on every channel.
 */
class InboundMessageService
{
    private const STOP_WORDS = ['stop', 'unsubscribe', 'الغاء', 'إلغاء', 'ايقاف', 'إيقاف'];

    public function record(string $channel, string $from, string $body, ?string $providerRef = null): NotificationLog
    {
        $log = NotificationLog::create([
            'to' => $from,
            'channel' => $channel,
            'provider_ref' => $providerRef,
            'event_key' => "inbound.{$channel}",
            'status' => NotificationLog::STATUS_DELIVERED,
            'meta' => ['direction' => 'inbound', 'body' => $body],
            'delivered_at' => now(),
        ]);

        $this->handleStopKeyword($channel, $from, $body);

        return $log;
    }

    private function handleStopKeyword(string $channel, string $from, string $body): void
    {
        if (! in_array(trim(mb_strtolower($body)), self::STOP_WORDS, true)) {
            return;
        }

        $patient = $this->matchPatient($from);
        if ($patient) {
            ConsentService::optOutMarketing($patient, 'stop_keyword');
            Log::info('STOP keyword → marketing opt-out', ['channel' => $channel, 'patient_id' => $patient->id]);
        }
    }

    /** Match a patient by the last 9 digits of the phone (format-agnostic). */
    private function matchPatient(string $from): ?Patient
    {
        $tail = substr(preg_replace('/[^0-9]/', '', $from), -9);
        if (! $tail) {
            return null;
        }

        return Patient::whereRaw("REPLACE(REPLACE(REPLACE(phone,'+',''),' ',''),'-','') LIKE ?", ["%{$tail}"])->first();
    }
}

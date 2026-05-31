<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;

/**
 * Applies a provider delivery receipt (DLR) to the matching notification_logs
 * row. Shared by the WhatsApp webhook and the SMS DLR endpoints. Status only
 * ever advances (queued→sent→delivered→read); a late lower receipt is ignored.
 */
class DeliveryReceiptService
{
    private const RANK = [
        NotificationLog::STATUS_QUEUED => 0,
        NotificationLog::STATUS_SENT => 1,
        NotificationLog::STATUS_FAILED => 1,
        NotificationLog::STATUS_DELIVERED => 2,
        NotificationLog::STATUS_READ => 3,
    ];

    /**
     * @param  string  $mappedStatus  one of NotificationLog::STATUS_*
     * @return bool whether a row was updated
     */
    public function apply(string $providerRef, string $channel, string $mappedStatus, ?string $error = null): bool
    {
        if ($providerRef === '' || ! isset(self::RANK[$mappedStatus])) {
            return false;
        }

        $log = NotificationLog::where('provider_ref', $providerRef)->where('channel', $channel)->first();
        if (! $log) {
            return false;
        }

        // Never downgrade a more-advanced status.
        if ((self::RANK[$mappedStatus] ?? 0) < (self::RANK[$log->status] ?? 0)) {
            return false;
        }

        $update = ['status' => $mappedStatus];
        if ($mappedStatus === NotificationLog::STATUS_DELIVERED && ! $log->delivered_at) {
            $update['delivered_at'] = now();
        }
        if ($mappedStatus === NotificationLog::STATUS_READ) {
            $update['read_at'] = $log->read_at ?? now();
            $update['delivered_at'] = $log->delivered_at ?? now();
        }
        if ($mappedStatus === NotificationLog::STATUS_FAILED) {
            $update['error'] = $error ?: 'Delivery failed (provider receipt).';
        }

        $log->update($update);

        return true;
    }
}

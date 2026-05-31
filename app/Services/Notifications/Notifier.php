<?php

namespace App\Services\Notifications;

use App\Jobs\ProcessNotificationJob;
use Illuminate\Database\Eloquent\Model;

/**
 * The single entry point every part of the system uses to send a notification.
 *
 *   Notifier::event('booking.confirmed', $patient, ['name' => $patient->full_name]);
 *
 * Routing, consent, quota, dedup, templating and channel fallback are all handled
 * downstream by NotificationService. Callers never pick a channel directly
 * (unless they pass $channels to force one).
 */
class Notifier
{
    /** Queue an event for async delivery (the normal path). */
    public static function event(string $eventKey, ?Model $recipient, array $data = [], ?array $channels = null): void
    {
        ProcessNotificationJob::dispatch($eventKey, $recipient, $data, $channels);
    }

    /** Send synchronously and return the created logs (cron jobs / tests). */
    public static function eventNow(string $eventKey, ?Model $recipient, array $data = [], ?array $channels = null): array
    {
        return app(NotificationService::class)->process($eventKey, $recipient, $data, $channels);
    }
}

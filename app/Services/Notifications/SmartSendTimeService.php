<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Learns the hour of day a recipient most often READS notifications, so marketing
 * can be timed for higher open rates. Returns null when there isn't enough signal
 * (send now). Only ever applied to non-time-critical (marketing) sends.
 */
class SmartSendTimeService
{
    private const WINDOW_DAYS = 90;

    private const MIN_READS = 3;

    /** The next datetime matching the recipient's modal read-hour, or null. */
    public function bestSendAt(Model $recipient): ?Carbon
    {
        $reads = NotificationLog::query()
            ->where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey())
            ->whereNotNull('read_at')
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->get(['read_at']);

        if ($reads->count() < self::MIN_READS) {
            return null;
        }

        $byHour = [];
        foreach ($reads as $r) {
            $h = (int) $r->read_at->format('H');
            $byHour[$h] = ($byHour[$h] ?? 0) + 1;
        }
        arsort($byHour);
        $hour = (int) array_key_first($byHour);

        $candidate = now()->setTime($hour, 0, 0);
        if ($candidate->lessThanOrEqualTo(now())) {
            $candidate->addDay();
        }

        return $candidate;
    }
}

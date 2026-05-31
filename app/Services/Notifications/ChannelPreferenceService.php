<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Learns each recipient's best external channel from their delivery history and
 * reorders the fallback group accordingly — so we try the channel they actually
 * engage with first (higher reach, lower cost). Gated by the
 * `notifications_smart_routing` setting; falls back to route priority on ties /
 * no history (stable sort).
 */
class ChannelPreferenceService
{
    private const EXTERNAL = ['whatsapp', 'sms', 'email'];

    private const WINDOW_DAYS = 90;

    /**
     * Reorder a routed channel list by the recipient's learned preference.
     * Non-external channels (e.g. in_app) keep their original position.
     */
    public function reorder(Model $recipient, array $channels): array
    {
        $scores = $this->scores($recipient);

        // Stable sort the external channels by score desc; keep others in place.
        $external = array_values(array_filter($channels, fn ($c) => in_array($c, self::EXTERNAL, true)));
        if (count($external) < 2) {
            return $channels; // nothing to reorder
        }

        // Decorate-sort-undecorate for a stable sort by score (desc), original index asc.
        $decorated = [];
        foreach ($external as $i => $c) {
            $decorated[] = ['c' => $c, 'i' => $i, 's' => $scores[$c] ?? 0];
        }
        usort($decorated, fn ($a, $b) => $b['s'] <=> $a['s'] ?: $a['i'] <=> $b['i']);
        $sortedExternal = array_column($decorated, 'c');

        // Re-thread into the original list, replacing external slots in new order.
        $out = [];
        $k = 0;
        foreach ($channels as $c) {
            $out[] = in_array($c, self::EXTERNAL, true) ? $sortedExternal[$k++] : $c;
        }

        return $out;
    }

    /** Per-channel engagement score over the recent window (read weighted highest). */
    public function scores(Model $recipient): array
    {
        $rows = NotificationLog::query()
            ->where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey())
            ->whereIn('channel', self::EXTERNAL)
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->get(['channel', 'status']);

        $scores = [];
        foreach ($rows as $r) {
            $w = match ($r->status) {
                NotificationLog::STATUS_READ => 3,
                NotificationLog::STATUS_DELIVERED => 2,
                NotificationLog::STATUS_SENT => 1,
                NotificationLog::STATUS_FAILED => -2,
                default => 0,
            };
            $scores[$r->channel] = ($scores[$r->channel] ?? 0) + $w;
        }

        return $scores;
    }
}

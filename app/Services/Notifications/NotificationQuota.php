<?php

namespace App\Services\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Setting;

/**
 * Enforces per-channel daily/monthly send caps plus a global daily cap.
 * A "send" counts any log row that left the system (sent/delivered/read) — not
 * skipped or failed rows. Caps of 0 / null mean "no limit".
 */
class NotificationQuota
{
    /** Statuses that count against quota (the message actually went out). */
    private const COUNTED = [
        NotificationLog::STATUS_SENT,
        NotificationLog::STATUS_DELIVERED,
        NotificationLog::STATUS_READ,
    ];

    /** Whether a send on this channel is allowed right now. */
    public function allows(string $channel): bool
    {
        // in_app is free and unlimited.
        if ($channel === 'in_app') {
            return true;
        }

        $cfg = NotificationChannel::for($channel);
        if (! $cfg) {
            return true;
        }

        if ($cfg->daily_cap && $this->channelCount($channel, 'day') >= $cfg->daily_cap) {
            return false;
        }

        if ($cfg->monthly_cap && $this->channelCount($channel, 'month') >= $cfg->monthly_cap) {
            return false;
        }

        $globalDaily = (int) Setting::get('notifications_global_daily_cap', '0');
        if ($globalDaily > 0 && $this->globalCount('day') >= $globalDaily) {
            return false;
        }

        return true;
    }

    /** Reason string when blocked, for logging skipped rows. */
    public function blockReason(string $channel): string
    {
        $cfg = NotificationChannel::for($channel);
        if ($cfg?->daily_cap && $this->channelCount($channel, 'day') >= $cfg->daily_cap) {
            return "daily cap reached ({$cfg->daily_cap})";
        }
        if ($cfg?->monthly_cap && $this->channelCount($channel, 'month') >= $cfg->monthly_cap) {
            return "monthly cap reached ({$cfg->monthly_cap})";
        }

        return 'global daily cap reached';
    }

    private function channelCount(string $channel, string $period): int
    {
        return NotificationLog::query()
            ->where('channel', $channel)
            ->whereIn('status', self::COUNTED)
            ->where('created_at', '>=', $this->since($period))
            ->count();
    }

    private function globalCount(string $period): int
    {
        return NotificationLog::query()
            ->whereIn('status', self::COUNTED)
            ->where('channel', '!=', 'in_app')
            ->where('created_at', '>=', $this->since($period))
            ->count();
    }

    private function since(string $period): \Illuminate\Support\Carbon
    {
        return $period === 'month' ? now()->startOfMonth() : now()->startOfDay();
    }
}

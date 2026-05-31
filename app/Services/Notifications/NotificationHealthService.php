<?php

namespace App\Services\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Operational health of the notifications pipeline. Used by /health and the
 * admin diagnostics page. A backlog of "queued" rows is the canary for a dead
 * queue worker — the single most common silent failure for the whole module.
 */
class NotificationHealthService
{
    public function report(): array
    {
        if (! Schema::hasTable('notification_logs')) {
            return ['ok' => true, 'enabled' => false];
        }

        // Queue backlog: queued > 1h almost always means queue:work is not running.
        $stuckQueued = NotificationLog::where('status', NotificationLog::STATUS_QUEUED)
            ->where('created_at', '<', now()->subHour())->count();

        // Channels switched on but missing the credentials they need.
        $unconfigured = [];
        foreach (NotificationChannel::where('enabled', true)->get() as $ch) {
            if (! $this->channelReady($ch)) {
                $unconfigured[] = $ch->channel;
            }
        }

        $failed24h = NotificationLog::where('status', NotificationLog::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())->count();
        $sent24h = NotificationLog::whereIn('status', [NotificationLog::STATUS_SENT, NotificationLog::STATUS_DELIVERED, NotificationLog::STATUS_READ])
            ->where('created_at', '>=', now()->subDay())->count();
        $attempted = $sent24h + $failed24h;
        $failureRate = $attempted > 0 ? round($failed24h / $attempted * 100, 1) : 0.0;

        $anyChannel = NotificationChannel::where('enabled', true)->exists() || Setting::get('sms_provider', 'none') !== 'none';

        // "ok" = no dead-worker backlog, no enabled-but-broken channels, failure rate sane.
        $ok = $stuckQueued === 0 && empty($unconfigured) && $failureRate < 50;

        return [
            'ok' => $ok,
            'enabled' => $anyChannel,
            'queue_backlog' => $stuckQueued,
            'channels_unconfigured' => $unconfigured,
            'failed_24h' => $failed24h,
            'sent_24h' => $sent24h,
            'failure_rate' => $failureRate,
        ];
    }

    private function channelReady(NotificationChannel $ch): bool
    {
        $cfg = $ch->config ?? [];

        return match ($ch->channel) {
            'in_app' => true,
            'sms' => Setting::get('sms_provider', 'none') !== 'none',
            'email' => ! empty($cfg['host']) && ! empty($cfg['username']),
            'whatsapp' => $ch->provider === 'bridge'
                ? ! empty($cfg['base_url'])
                : (! empty($cfg['phone_number_id']) && ! empty($cfg['access_token'])),
            default => true,
        };
    }
}

<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;

/**
 * Re-attempts recently-failed external notifications, up to a max retry count.
 * Scheduled in console.php. in_app never fails so it's excluded.
 */
class NotificationsRetryCommand extends Command
{
    protected $signature = 'notifications:retry
        {--hours=24 : Only retry logs that failed within this many hours}
        {--max=3 : Skip logs already retried this many times}
        {--limit=200 : Max logs to process per run}
        {--dry : Report only, do not send}';

    protected $description = 'Retry failed notification deliveries (WhatsApp/SMS/Email)';

    public function handle(NotificationService $service): int
    {
        $since = now()->subHours((int) $this->option('hours'));
        $max = (int) $this->option('max');

        $logs = NotificationLog::where('status', NotificationLog::STATUS_FAILED)
            ->where('channel', '!=', 'in_app')
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get()
            ->filter(fn ($l) => (($l->meta['retry_count'] ?? 0) < $max) && ! empty($l->to) && ! empty($l->meta['body'] ?? null));

        if ($logs->isEmpty()) {
            $this->info('No eligible failed notifications to retry.');

            return self::SUCCESS;
        }

        if ($this->option('dry')) {
            $this->table(['ID', 'Channel', 'To', 'Retries', 'Error'],
                $logs->map(fn ($l) => [$l->id, $l->channel, $l->to, $l->meta['retry_count'] ?? 0, str($l->error)->limit(40)]));
            $this->info("{$logs->count()} would be retried.");

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        foreach ($logs as $log) {
            if ($service->resend($log)) {
                $ok++;
            } else {
                $fail++;
            }
        }

        $this->info("Retried {$logs->count()}: {$ok} sent, {$fail} still failing.");

        return self::SUCCESS;
    }
}

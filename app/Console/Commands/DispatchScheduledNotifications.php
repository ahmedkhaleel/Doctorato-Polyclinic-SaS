<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;

/**
 * Processes notifications that were held (e.g. quiet hours) and are now due.
 * Re-runs them through the hub with deferral disabled so they can't loop.
 */
class DispatchScheduledNotifications extends Command
{
    protected $signature = 'notifications:dispatch-scheduled {--limit=500}';

    protected $description = 'Send held/scheduled notifications whose time has come';

    public function handle(NotificationService $service): int
    {
        $due = ScheduledNotification::where('status', ScheduledNotification::STATUS_PENDING)
            ->where('send_after', '<=', now())
            ->orderBy('send_after')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($due as $row) {
            try {
                $service->process(
                    $row->event_key,
                    $row->recipient,            // morphTo (null-safe)
                    $row->data ?? [],
                    $row->channels,
                    allowDefer: false,          // never re-defer a held send
                );
                $row->update(['status' => ScheduledNotification::STATUS_PROCESSED, 'processed_at' => now()]);
            } catch (\Throwable $e) {
                $row->update(['status' => ScheduledNotification::STATUS_PROCESSED, 'processed_at' => now()]);
                report($e);
            }
        }

        $this->info("{$due->count()} held notification(s) dispatched.");

        return self::SUCCESS;
    }
}

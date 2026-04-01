<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for dispatching notifications asynchronously.
 * Wraps any Notification instance and sends it to the specified notifiable(s).
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [15, 60, 120];

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public mixed $notifiable,
        public Notification $notification,
        public ?string $context = null,
    ) {}

    public function handle(): void
    {
        try {
            if (is_iterable($this->notifiable)) {
                \Illuminate\Support\Facades\Notification::send($this->notifiable, $this->notification);
            } else {
                $this->notifiable->notify($this->notification);
            }

            Log::info('Notification sent via queue', [
                'notification' => get_class($this->notification),
                'context' => $this->context,
            ]);
        } catch (\Exception $e) {
            Log::error('Notification job failed', [
                'notification' => get_class($this->notification),
                'context' => $this->context,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Notification job permanently failed', [
            'notification' => get_class($this->notification),
            'context' => $this->context,
            'error' => $exception?->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['notification', get_class($this->notification), 'context:' . ($this->context ?? 'general')];
    }
}

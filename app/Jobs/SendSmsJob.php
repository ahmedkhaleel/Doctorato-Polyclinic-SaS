<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for sending SMS messages asynchronously.
 * Retries up to 3 times with exponential backoff on failure.
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff intervals (seconds) between retries.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public string $phone,
        public string $message,
        public ?string $senderName = null,
        public ?string $context = null,
    ) {}

    public function handle(): void
    {
        $result = SmsService::send($this->phone, $this->message, $this->senderName);

        if (! $result['success']) {
            Log::warning('SMS job failed', [
                'phone' => $this->phone,
                'context' => $this->context,
                'error' => $result['message'],
                'provider' => $result['provider'],
                'attempt' => $this->attempts(),
            ]);

            // Retry if attempts remaining
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 120);
            }
        } else {
            Log::info('SMS sent via queue', [
                'phone' => $this->phone,
                'context' => $this->context,
                'provider' => $result['provider'],
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('SMS job permanently failed', [
            'phone' => $this->phone,
            'context' => $this->context,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['sms', 'phone:' . $this->phone, 'context:' . ($this->context ?? 'general')];
    }
}

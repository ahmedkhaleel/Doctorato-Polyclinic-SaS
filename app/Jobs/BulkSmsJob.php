<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for sending bulk SMS messages (e.g., dental lab order notifications).
 * Processes a batch of phone/message pairs sequentially with rate limiting.
 * Implements ShouldBeUnique to prevent duplicate bulk sends.
 */
class BulkSmsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300; // 5 minutes

    public int $uniqueFor = 600; // 10 minutes

    /**
     * @param  array<int, array{phone: string, message: string}>  $recipients
     */
    public function __construct(
        public array $recipients,
        public ?string $context = null,
        public ?string $batchId = null,
    ) {
        $this->batchId = $batchId ?? uniqid('bulk_sms_');
    }

    public function handle(): void
    {
        $sent = 0;
        $failed = 0;

        foreach ($this->recipients as $recipient) {
            $result = SmsService::send($recipient['phone'], $recipient['message']);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
                Log::warning('Bulk SMS: individual send failed', [
                    'batch_id' => $this->batchId,
                    'phone' => $recipient['phone'],
                    'error' => $result['message'],
                ]);
            }

            // Rate limit: wait 200ms between messages to avoid provider throttling
            usleep(200_000);
        }

        Log::info('Bulk SMS batch completed', [
            'batch_id' => $this->batchId,
            'context' => $this->context,
            'total' => count($this->recipients),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Bulk SMS batch failed', [
            'batch_id' => $this->batchId,
            'context' => $this->context,
            'total' => count($this->recipients),
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * The unique ID for preventing duplicate jobs.
     */
    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function tags(): array
    {
        return ['sms', 'bulk', "batch:{$this->batchId}", 'context:' . ($this->context ?? 'general')];
    }
}

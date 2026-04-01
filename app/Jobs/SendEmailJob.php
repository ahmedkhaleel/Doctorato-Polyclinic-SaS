<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Queue job for sending emails asynchronously.
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 180];

    public function __construct(
        public string $to,
        public Mailable $mailable,
        public ?string $context = null,
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->to)->send($this->mailable);

            Log::info('Email sent via queue', [
                'to' => $this->to,
                'mailable' => get_class($this->mailable),
                'context' => $this->context,
            ]);
        } catch (\Exception $e) {
            Log::error('Email job failed', [
                'to' => $this->to,
                'mailable' => get_class($this->mailable),
                'context' => $this->context,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // Let queue retry
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Email job permanently failed', [
            'to' => $this->to,
            'mailable' => get_class($this->mailable),
            'context' => $this->context,
            'error' => $exception?->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['email', 'to:' . $this->to, 'context:' . ($this->context ?? 'general')];
    }
}

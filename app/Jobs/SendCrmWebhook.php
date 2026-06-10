<?php

namespace App\Jobs;

use App\Models\CrmSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CRM-3 — delivers one signed CRM webhook. 3 attempts with growing backoff;
 * a non-2xx response throws so the queue retries. Signature is HMAC-SHA256 of
 * the raw JSON body using the shared secret from CRM settings.
 */
class SendCrmWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int,int> seconds before each retry */
    public array $backoff = [60, 300];

    public function __construct(
        public readonly string $url,
        public readonly string $event,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $body = json_encode($this->payload, JSON_UNESCAPED_UNICODE);
        $secret = (string) CrmSetting::get('webhook_secret', '');
        $signature = $secret !== '' ? hash_hmac('sha256', $body, $secret) : '';

        $response = Http::timeout(10)
            ->withHeaders(array_filter([
                'Content-Type' => 'application/json',
                'X-Doctorato-Event' => $this->event,
                'X-Doctorato-Signature' => $signature !== '' ? 'sha256='.$signature : null,
            ]))
            ->withBody($body, 'application/json')
            ->post($this->url);

        if (! $response->successful()) {
            throw new \RuntimeException("CRM webhook [{$this->event}] to {$this->url} failed: HTTP {$response->status()}");
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('[crm.webhook] delivery failed after retries', [
            'event' => $this->event,
            'url' => $this->url,
            'error' => $e->getMessage(),
        ]);
    }
}

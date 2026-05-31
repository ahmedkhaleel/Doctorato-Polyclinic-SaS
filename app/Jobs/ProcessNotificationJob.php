<?php

namespace App\Jobs;

use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public function __construct(
        public string $eventKey,
        public ?Model $recipient,
        public array $data = [],
        public ?array $forceChannels = null,
    ) {}

    public function handle(NotificationService $service): void
    {
        $service->process($this->eventKey, $this->recipient, $this->data, $this->forceChannels);
    }
}

<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\SmsNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for sending booking-related SMS notifications.
 * Handles: confirmation, reminder, same-day reminder.
 */
class ProcessBookingSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public Booking $booking,
        public string $type = 'confirmed', // confirmed, reminder, same_day
    ) {}

    public function handle(): void
    {
        $result = match ($this->type) {
            'confirmed' => SmsNotificationService::bookingConfirmed($this->booking),
            'reminder' => SmsNotificationService::bookingReminder($this->booking),
            'same_day' => SmsNotificationService::sameDayReminder($this->booking),
            default => ['success' => false, 'message' => "Unknown SMS type: {$this->type}"],
        };

        if (! $result['success']) {
            Log::warning('Booking SMS job failed', [
                'booking_id' => $this->booking->id,
                'type' => $this->type,
                'error' => $result['message'],
            ]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Booking SMS job permanently failed', [
            'booking_id' => $this->booking->id,
            'type' => $this->type,
            'error' => $exception?->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['sms', 'booking', "booking:{$this->booking->id}", "type:{$this->type}"];
    }
}

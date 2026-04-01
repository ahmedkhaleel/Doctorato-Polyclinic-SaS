<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Models\Setting;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmedSms
{
    public function handle(BookingConfirmed $event): void
    {
        if (Setting::get('sms_on_booking_confirmed', '0') !== '1') {
            return;
        }

        try {
            SmsNotificationService::bookingConfirmed($event->booking);
        } catch (\Throwable $e) {
            Log::warning('SMS booking confirmation failed via event', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

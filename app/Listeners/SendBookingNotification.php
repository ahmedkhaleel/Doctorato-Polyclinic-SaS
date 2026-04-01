<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Mail\NewBookingNotification;
use App\Models\Setting;
use App\Jobs\SendEmailJob;

class SendBookingNotification
{
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking->load(['service', 'doctor']);
        $adminEmail = Setting::get('email', 'info@aura-clinic.net');

        SendEmailJob::dispatch($adminEmail, new NewBookingNotification($booking), 'new_booking');
    }
}

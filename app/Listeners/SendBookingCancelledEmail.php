<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Notifications\BookingStatusEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendBookingCancelledEmail
{
    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking->load([
            'service', 'doctor', 'patient',
            'bookingServices.service', 'bookingServices.doctor',
            'appointments',
        ]);

        $email = $booking->email
            ?: $booking->patient?->email
            ?: $booking->patient?->user?->email;

        if (! $email) return;

        try {
            Notification::route('mail', $email)
                ->notify(new BookingStatusEmail($booking, 'cancelled', $event->reason));
        } catch (\Throwable $e) {
            Log::warning('[booking-cancelled-email] failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\BookingStatusEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Sends the patient the branded "Your booking is confirmed" email when
 * a secretary or admin moves a booking from unconfirmed → confirmed.
 *
 * Best-effort: a mail backend hiccup must not prevent the workflow
 * service from completing the confirm transition.
 */
class SendBookingConfirmedEmail
{
    public function handle(BookingConfirmed $event): void
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
                ->notify(new BookingStatusEmail($booking, 'confirmed'));
        } catch (\Throwable $e) {
            Log::warning('[booking-confirmed-email] failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}

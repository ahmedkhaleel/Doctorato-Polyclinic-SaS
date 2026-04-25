<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Jobs\SendEmailJob;
use App\Mail\NewBookingNotification;
use App\Models\Setting;
use App\Notifications\BookingStatusEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendBookingNotification
{
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking->load(['service', 'doctor', 'bookingServices.service', 'bookingServices.doctor', 'patient', 'appointments']);

        // 1. Notify admin (existing behavior)
        $adminEmail = Setting::get('email', 'info@doctorato.com');
        SendEmailJob::dispatch($adminEmail, new NewBookingNotification($booking), 'new_booking');

        // 2. Notify the patient with the branded "we got your request" email.
        // Honors per-patient notify_email_bookings preference. Best-effort.
        if ($this->patientWantsBookingEmails($booking)) {
            try {
                $recipient = $this->resolvePatientRecipient($booking);
                if ($recipient) {
                    Notification::route('mail', $recipient['email'])
                        ->notify(new BookingStatusEmail($booking, 'created'));
                }
            } catch (\Throwable $e) {
                Log::warning('[booking-created-email] failed', [
                    'booking_id' => $booking->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Anonymous (no linked patient) bookings: assume opt-in. Linked
     * patients: respect their notify_email_bookings preference.
     */
    private function patientWantsBookingEmails($booking): bool
    {
        if (! $booking->patient) return true;
        return $booking->patient->wantsNotification('bookings', 'email');
    }

    /**
     * Find the right email + name for the patient. Website bookings carry
     * email/full_name on the row itself; secretary bookings link a Patient.
     */
    private function resolvePatientRecipient($booking): ?array
    {
        $email = $booking->email ?: $booking->patient?->email ?: $booking->patient?->user?->email;
        $name  = $booking->full_name ?: $booking->patient?->full_name ?: 'Patient';
        if (! $email) return null;

        return ['email' => $email, 'name' => $name];
    }
}

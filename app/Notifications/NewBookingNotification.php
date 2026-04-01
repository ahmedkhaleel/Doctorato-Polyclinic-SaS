<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewBookingNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Service name: website bookings have service_id, secretary bookings use bookingServices pivot
        $serviceName = $this->booking->service?->name_en
            ?? $this->booking->bookingServices?->first()?->service?->name_en
            ?? 'General';

        // Doctor name: website bookings have doctor_id, secretary bookings have doctor per service
        $doctorName = $this->booking->doctor?->user?->name
            ?? $this->booking->bookingServices?->first()?->doctor?->user?->name
            ?? null;

        return [
            'type' => 'new_booking',
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'patient_name' => $this->booking->full_name ?? $this->booking->patient?->full_name ?? 'Patient',
            'service_name' => $serviceName,
            'doctor_name' => $doctorName,
            'preferred_date' => $this->booking->preferred_date?->format('Y-m-d')
                ?? $this->booking->appointments?->first()?->appointment_date?->format('Y-m-d'),
            'preferred_time' => $this->booking->preferred_time
                ?? $this->booking->appointments?->first()?->start_time,
            'message' => "New booking from {$this->booking->full_name}",
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification
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
        return [
            'type' => 'booking_reminder',
            'booking_id' => $this->booking->id,
            'patient_name' => $this->booking->full_name,
            'service_name' => $this->booking->service?->name_en ?? 'General',
            'preferred_date' => $this->booking->preferred_date?->format('Y-m-d'),
            'preferred_time' => $this->booking->preferred_time,
            'message' => "Reminder: {$this->booking->full_name} has an appointment today",
        ];
    }
}

<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\User;
use App\Services\Notifications\Notifier;
use App\Services\Notifications\StaffNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * In-app alert to front-desk staff (secretary + admins) when a new booking
 * arrives, plus the assigned doctor's own user account if there is one.
 */
class NotifyStaffOnBooking implements ShouldQueue
{
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;
        $name = $booking->full_name ?? $booking->patient?->full_name ?? '—';
        $module = $booking->module ?? '';
        $date = $booking->preferred_date?->format('Y-m-d') ?? '';
        $time = $booking->preferred_time ?? '';

        $body = trim("حجز جديد: {$name} — {$module} {$date} {$time}");
        $data = [
            'title' => 'حجز جديد',
            'body' => $body,
            'url' => "/admin/bookings/{$booking->id}",
            'meta' => ['title' => 'حجز جديد', 'body' => $body, 'url' => "/admin/bookings/{$booking->id}", 'booking_id' => $booking->id],
        ];

        StaffNotifier::toRoles(['secretary', 'admin', 'super_admin'], 'staff.booking_new', $data);

        // The assigned doctor (if the doctor row is linked to a portal user).
        $doctorUser = $booking->doctor?->user;
        if ($doctorUser instanceof User) {
            Notifier::event('staff.booking_new', $doctorUser, array_merge($data, [
                'meta' => array_merge($data['meta'], ['url' => "/doctor/bookings/{$booking->id}"]),
                'url' => "/doctor/bookings/{$booking->id}",
            ]), ['in_app']);
        }
    }
}

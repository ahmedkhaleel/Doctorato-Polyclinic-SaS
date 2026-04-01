<?php

namespace App\Listeners;

use App\Events\VisitCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateBookingStatusOnCompletion implements ShouldQueue
{
    public function handle(VisitCompleted $event): void
    {
        $visit = $event->visit;
        $booking = $visit->booking;

        if (! $booking) {
            return;
        }

        // Check if all appointments for this booking are completed
        $totalAppointments = $booking->appointments()->count();
        $completedAppointments = $booking->appointments()
            ->where('status', 'completed')
            ->count();

        if ($totalAppointments > 0 && $completedAppointments >= $totalAppointments) {
            $booking->update(['status' => 'completed']);
        }
    }
}

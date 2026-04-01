<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send reminder notifications to doctors for today\'s confirmed bookings';

    public function handle(): int
    {
        $bookings = Booking::with(['doctor.user', 'service'])
            ->where('status', 'confirmed')
            ->whereDate('preferred_date', today())
            ->whereNotNull('doctor_id')
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            if ($booking->doctor?->user) {
                $booking->doctor->user->notify(new BookingReminderNotification($booking));
                $count++;
            }
        }

        $this->info("Sent {$count} booking reminders for today.");

        return self::SUCCESS;
    }
}

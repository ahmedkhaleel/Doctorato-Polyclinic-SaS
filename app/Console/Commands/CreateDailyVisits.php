<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingWorkflowService;
use Illuminate\Console\Command;

class CreateDailyVisits extends Command
{
    protected $signature = 'bookings:create-daily-visits';

    protected $description = 'Auto-create visits for today\'s appointments in all in-progress bookings';

    public function handle(BookingWorkflowService $workflowService): int
    {
        $bookings = Booking::where('status', 'in_progress')
            ->whereHas('appointments', function ($q) {
                $q->whereDate('appointment_date', today())
                    ->whereNull('visit_id')
                    ->whereNotIn('status', ['cancelled', 'no_show', 'completed']);
            })
            ->get();

        $totalVisits = 0;

        foreach ($bookings as $booking) {
            $visits = $workflowService->createVisitsForTodayAppointments($booking, $booking->created_by ?? 1);
            $totalVisits += count($visits);
        }

        $this->info("Created {$totalVisits} visit(s) for today's appointments across {$bookings->count()} booking(s).");

        return self::SUCCESS;
    }
}

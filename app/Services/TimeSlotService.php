<?php

namespace App\Services;

use App\Models\BookingAppointment;
use App\Models\DoctorSchedule;
use App\Models\DoctorVacation;
use App\Models\PackageBundleBookingAppointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeSlotService
{
    /**
     * Get available time slots for a doctor on a specific date.
     */
    public function getAvailableSlots(int $doctorId, string $date, int $durationMinutes = 30): Collection
    {
        $carbonDate = Carbon::parse($date);

        // 1. Check if doctor is on vacation
        $onVacation = DoctorVacation::where('doctor_id', $doctorId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($onVacation) {
            return collect();
        }

        // 2. Get doctor's schedule for this day of week
        $dayOfWeek = $this->carbonDayToSystemDay($carbonDate->dayOfWeek);

        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return collect();
        }

        // 3. Generate all possible slots
        $allSlots = $this->generateSlots(
            $schedule->start_time,
            $schedule->end_time,
            $durationMinutes
        );

        // 4. Get existing booked appointments for this doctor on this date (both tables)
        $bookedSlots = BookingAppointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get(['start_time', 'end_time']);

        $bundleBookedSlots = PackageBundleBookingAppointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get(['start_time', 'end_time']);

        $bookedSlots = $bookedSlots->merge($bundleBookedSlots);

        // 5. Filter out booked slots
        return $allSlots->filter(function ($slot) use ($bookedSlots) {
            foreach ($bookedSlots as $booked) {
                $bookedStart = substr($booked->start_time, 0, 5);
                $bookedEnd = substr($booked->end_time, 0, 5);
                // Overlap check
                if ($slot['start'] < $bookedEnd && $slot['end'] > $bookedStart) {
                    return false;
                }
            }
            return true;
        })->values();
    }

    /**
     * Check if a specific slot is available.
     */
    public function isSlotAvailable(
        int $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeBookingAppointmentId = null,
        ?int $excludeBundleAppointmentId = null
    ): bool {
        // Vacation check
        $onVacation = DoctorVacation::where('doctor_id', $doctorId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($onVacation) {
            return false;
        }

        // Schedule check
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $this->carbonDayToSystemDay($carbonDate->dayOfWeek);

        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return false;
        }

        // Check time is within schedule
        if ($startTime < substr($schedule->start_time, 0, 5) || $endTime > substr($schedule->end_time, 0, 5)) {
            return false;
        }

        // Conflict check against booking appointments
        $bookingConflict = BookingAppointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($excludeBookingAppointmentId, fn ($q) => $q->where('id', '!=', $excludeBookingAppointmentId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($bookingConflict) {
            return false;
        }

        // Conflict check against bundle appointments
        return !PackageBundleBookingAppointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($excludeBundleAppointmentId, fn ($q) => $q->where('id', '!=', $excludeBundleAppointmentId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    /**
     * Get available dates for a doctor within a range.
     */
    public function getAvailableDates(int $doctorId, string $fromDate, string $toDate, int $durationMinutes = 30): Collection
    {
        $dates = collect();
        $current = Carbon::parse($fromDate);
        $end = Carbon::parse($toDate);

        while ($current->lte($end)) {
            $slots = $this->getAvailableSlots($doctorId, $current->toDateString(), $durationMinutes);
            if ($slots->isNotEmpty()) {
                $dates->push([
                    'date' => $current->toDateString(),
                    'available_slots_count' => $slots->count(),
                ]);
            }
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Generate time slots from start to end with given duration.
     */
    protected function generateSlots(string $startTime, string $endTime, int $durationMinutes): Collection
    {
        $slots = collect();
        $current = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);
            $slots->push([
                'start' => $current->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'start_12h' => $current->format('g:i A'),
                'end_12h' => $slotEnd->format('g:i A'),
            ]);
            $current->addMinutes($durationMinutes);
        }

        return $slots;
    }

    /**
     * Convert Carbon dayOfWeek (0=Sunday) to system dayOfWeek (0=Saturday).
     */
    protected function carbonDayToSystemDay(int $carbonDay): int
    {
        // Carbon: 0=Sunday, 1=Monday, ..., 6=Saturday
        // System: 0=Saturday, 1=Sunday, ..., 6=Friday
        $map = [0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 0];

        return $map[$carbonDay];
    }
}

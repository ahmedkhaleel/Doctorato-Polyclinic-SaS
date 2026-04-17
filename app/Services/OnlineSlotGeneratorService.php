<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\OnlineConsultation;
use Carbon\Carbon;

class OnlineSlotGeneratorService
{
    /**
     * Returns array of ['start_time' => 'HH:MM', 'end_time' => 'HH:MM', 'available' => bool]
     */
    public function getAvailableSlots(Doctor $doctor, Carbon $date): array
    {
        if (!$doctor->online_consultation_enabled) {
            return [];
        }

        // MySQL day_of_week: 0=Sat 1=Sun 2=Mon 3=Tue 4=Wed 5=Thu 6=Fri (per existing system)
        $dayOfWeek = $this->carbonToSystemDay($date);

        $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereIn('mode', [DoctorSchedule::MODE_ONLINE, DoctorSchedule::MODE_BOTH])
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $slots = [];
        $duration = $doctor->online_session_duration_minutes ?: 30;

        foreach ($schedules as $schedule) {
            $slotDuration = $schedule->slot_duration_minutes ?: $duration;
            $buffer = $schedule->buffer_minutes ?: 5;

            $startDT = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
            $endDT = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

            while ($startDT->copy()->addMinutes($slotDuration)->lessThanOrEqualTo($endDT)) {
                $slotEnd = $startDT->copy()->addMinutes($slotDuration);
                $slots[] = [
                    'start_time' => $startDT->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'datetime' => $startDT->copy(),
                ];
                $startDT = $slotEnd->copy()->addMinutes($buffer);
            }
        }

        // Now check availability against existing consultations
        $existingBusy = OnlineConsultation::where('doctor_id', $doctor->id)
            ->where('scheduled_date', $date->format('Y-m-d'))
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->get(['start_time', 'end_time'])
            ->map(fn ($c) => $c->start_time instanceof Carbon
                ? $c->start_time->format('H:i')
                : substr((string) $c->start_time, 0, 5))
            ->toArray();

        $now = now();

        $result = [];
        foreach ($slots as $slot) {
            $isAvailable = !in_array($slot['start_time'], $existingBusy)
                && $slot['datetime']->greaterThan($now);

            $result[] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'available' => $isAvailable,
            ];
        }

        return $result;
    }

    public function isSlotAvailable(Doctor $doctor, Carbon $datetime): bool
    {
        $slots = $this->getAvailableSlots($doctor, $datetime);
        $timeStr = $datetime->format('H:i');
        foreach ($slots as $slot) {
            if ($slot['start_time'] === $timeStr) {
                return $slot['available'];
            }
        }
        return false;
    }

    private function carbonToSystemDay(Carbon $date): int
    {
        // Carbon dayOfWeek: 0=Sun, 1=Mon ... 6=Sat
        // System: 0=Sat, 1=Sun, 2=Mon ... 6=Fri
        $map = [0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 0];
        return $map[$date->dayOfWeek];
    }
}

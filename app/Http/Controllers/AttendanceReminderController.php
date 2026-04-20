<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cross-panel attendance reminder API.
 *
 * Used by the AttendanceReminder popup on Admin / Doctor / Secretary layouts.
 * Tells the frontend whether to show a check-in or check-out popup
 * based on the current time and the user's scheduled work hours.
 */
class AttendanceReminderController extends Controller
{
    /**
     * Grace window (minutes) before shift start / after shift end in which
     * the popup is still considered "relevant" for check-in / check-out.
     */
    const CHECK_IN_WINDOW_BEFORE = 30;   // can check in 30 min before shift
    const CHECK_IN_WINDOW_AFTER  = 240;  // still show check-in up to 4h after start
    const CHECK_OUT_WINDOW_AFTER = 120;  // can check out 2h after shift end

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['show' => false, 'reason' => 'unauthenticated']);
        }

        $roleName = $user->role?->name;
        if (! in_array($roleName, ['doctor', 'secretary', 'admin', 'super_admin'], true)) {
            return response()->json(['show' => false, 'reason' => 'role_not_supported']);
        }

        $now = now();
        $today = $now->toDateString();
        $dow = $now->dayOfWeek; // 0 = Sunday

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Find today's shift window for this user
        $shiftWindow = $this->findShiftWindow($user, $roleName, $dow);

        // For secretaries and admins without a shift record, fall back to a
        // generic 9-5 window so they still get the reminder.
        if (! $shiftWindow && in_array($roleName, ['secretary', 'admin', 'super_admin'], true)) {
            $shiftWindow = [
                'start' => $now->copy()->setTime(9, 0),
                'end'   => $now->copy()->setTime(17, 0),
                'source' => 'default',
            ];
        }

        // Doctors without a schedule → no reminder
        if (! $shiftWindow) {
            return response()->json(['show' => false, 'reason' => 'no_shift_today']);
        }

        // Determine action: check-in or check-out
        if (! $attendance || empty($attendance->check_in)) {
            // Not yet checked in
            $checkInFrom = $shiftWindow['start']->copy()->subMinutes(self::CHECK_IN_WINDOW_BEFORE);
            $checkInUntil = $shiftWindow['start']->copy()->addMinutes(self::CHECK_IN_WINDOW_AFTER);

            if ($now->between($checkInFrom, $checkInUntil)) {
                return response()->json([
                    'show'   => true,
                    'action' => 'check_in',
                    'shift'  => [
                        'start' => $shiftWindow['start']->format('H:i'),
                        'end'   => $shiftWindow['end']->format('H:i'),
                        'source' => $shiftWindow['source'],
                    ],
                    // Secretaries get a 5-min nag; doctors just once per shift window
                    'repeat_interval_minutes' => $roleName === 'doctor' ? 0 : 5,
                    'late' => $now->greaterThan($shiftWindow['start']),
                ]);
            }
            return response()->json(['show' => false, 'reason' => 'outside_check_in_window']);
        }

        if (empty($attendance->check_out)) {
            // Checked in but not yet checked out
            $checkOutFrom = $shiftWindow['end']->copy()->subMinutes(15);
            $checkOutUntil = $shiftWindow['end']->copy()->addMinutes(self::CHECK_OUT_WINDOW_AFTER);

            if ($now->between($checkOutFrom, $checkOutUntil)) {
                return response()->json([
                    'show'   => true,
                    'action' => 'check_out',
                    'shift'  => [
                        'start' => $shiftWindow['start']->format('H:i'),
                        'end'   => $shiftWindow['end']->format('H:i'),
                        'source' => $shiftWindow['source'],
                        'checked_in_at' => $attendance->check_in,
                    ],
                    'repeat_interval_minutes' => 0,
                ]);
            }
            return response()->json(['show' => false, 'reason' => 'outside_check_out_window']);
        }

        // Already fully checked in + out for today
        return response()->json([
            'show'   => false,
            'reason' => 'already_completed',
            'attendance' => [
                'check_in'  => $attendance->check_in,
                'check_out' => $attendance->check_out,
            ],
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $now = now();
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
        ]);

        if (! empty($attendance->check_in)) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل حضورك بالفعل اليوم',
                'check_in' => $attendance->check_in,
            ]);
        }

        $attendance->check_in = $now->format('H:i');
        $attendance->status = $now->format('H:i') > '09:00' ? 'late' : 'present';
        $attendance->overtime_hours = $attendance->overtime_hours ?? 0;
        if (! empty($data['lat']) && ! empty($data['lng'])) {
            $attendance->check_in_lat = $data['lat'];
            $attendance->check_in_lng = $data['lng'];
        }
        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح',
            'check_in' => $attendance->check_in,
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم تسجيل حضورك اليوم',
            ]);
        }

        if (! empty($attendance->check_out)) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل انصرافك بالفعل اليوم',
                'check_out' => $attendance->check_out,
            ]);
        }

        $attendance->check_out = now()->format('H:i');
        if (! empty($data['lat']) && ! empty($data['lng'])) {
            $attendance->check_out_lat = $data['lat'];
            $attendance->check_out_lng = $data['lng'];
        }
        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح',
            'check_out' => $attendance->check_out,
        ]);
    }

    /**
     * Find today's scheduled shift window for the user.
     * Returns ['start' => Carbon, 'end' => Carbon, 'source' => 'doctor'|'shift']
     * or null if no shift is scheduled.
     */
    private function findShiftWindow($user, string $roleName, int $dow): ?array
    {
        $now = now();
        $today = $now->toDateString();

        // Doctor: use doctor_schedules by day_of_week
        if ($roleName === 'doctor') {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (! $doctor) return null;

            $schedule = DoctorSchedule::where('doctor_id', $doctor->id)
                ->where('day_of_week', $dow)
                ->where('is_active', true)
                ->first();

            if (! $schedule) return null;

            return [
                'start' => $now->copy()->setTimeFromTimeString($schedule->start_time),
                'end'   => $now->copy()->setTimeFromTimeString($schedule->end_time),
                'source' => 'doctor_schedule',
            ];
        }

        // Secretary/Admin: use employee_shifts if available
        $empShift = EmployeeShift::where('user_id', $user->id)
            ->where('day_of_week', $dow)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today);
            })
            ->with('shift')
            ->first();

        if ($empShift && $empShift->shift) {
            return [
                'start' => $now->copy()->setTimeFromTimeString($empShift->shift->start_time),
                'end'   => $now->copy()->setTimeFromTimeString($empShift->shift->end_time),
                'source' => 'employee_shift',
            ];
        }

        return null;
    }
}

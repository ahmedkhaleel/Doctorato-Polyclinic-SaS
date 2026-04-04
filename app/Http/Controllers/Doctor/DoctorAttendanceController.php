<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Attendance;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorAttendanceController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $records = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        $summary = [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'leave' => $records->where('status', 'leave')->count(),
            'overtime_hours' => $records->sum('overtime_hours'),
        ];

        // Today's attendance for quick check-in/out
        $today = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        return Inertia::render('Doctor/MyAttendance/Index', [
            'records' => $records,
            'summary' => $summary,
            'filters' => ['month' => $month, 'year' => $year],
            'today' => $today,
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $now = now();
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
        ]);

        $attendance->check_in = $now->format('H:i');
        $attendance->status = $now->format('H:i') > '09:00' ? 'late' : 'present';
        $attendance->overtime_hours = $attendance->overtime_hours ?? 0;
        $attendance->notes = $attendance->notes ?? '';

        if (!empty($data['lat']) && !empty($data['lng'])) {
            $attendance->check_in_lat = $data['lat'];
            $attendance->check_in_lng = $data['lng'];
        }

        $attendance->save();
        AuditLogger::log($attendance->wasRecentlyCreated ? 'created' : 'updated', $attendance);

        return redirect()->back()->with('success', 'تم تسجيل الحضور بنجاح');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'لا يوجد تسجيل حضور لليوم');
        }

        $attendance->check_out = now()->format('H:i');

        if (!empty($data['lat']) && !empty($data['lng'])) {
            $attendance->check_out_lat = $data['lat'];
            $attendance->check_out_lng = $data['lng'];
        }

        $attendance->save();
        AuditLogger::log('updated', $attendance);

        return redirect()->back()->with('success', 'تم تسجيل الانصراف بنجاح');
    }
}

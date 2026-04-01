<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Attendance;
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

        return Inertia::render('Doctor/MyAttendance/Index', [
            'records' => $records,
            'summary' => $summary,
            'filters' => ['month' => $month, 'year' => $year],
        ]);
    }
}

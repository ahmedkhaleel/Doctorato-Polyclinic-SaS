<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryAttendanceController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

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

        return Inertia::render('Secretary/MyAttendance/Index', [
            'records' => $records,
            'summary' => $summary,
            'filters' => ['month' => $month, 'year' => $year],
        ]);
    }
}

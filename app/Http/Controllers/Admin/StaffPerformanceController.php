<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StaffPerformanceController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $employees = Employee::with(['user:id,name', 'department:id,name_en,name_ar'])
            ->where('status', 'active')
            ->get();

        $staffKpis = $employees->map(function (Employee $emp) use ($dateFrom, $dateTo) {
            $attendances = Attendance::where('user_id', $emp->user_id)
                ->whereDate('date', '>=', $dateFrom)
                ->whereDate('date', '<=', $dateTo);

            $totalDays = (int) $attendances->clone()->count();
            $presentDays = (int) $attendances->clone()->where('status', 'present')->count();
            $absentDays = (int) $attendances->clone()->where('status', 'absent')->count();
            $lateDays = (int) $attendances->clone()->where('status', 'late')->count();
            $overtimeHours = (float) $attendances->clone()->sum('overtime_hours');

            // Attendance rate
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;

            // Avg working hours (check_in to check_out)
            $avgHours = (float) Attendance::where('user_id', $emp->user_id)
                ->whereDate('date', '>=', $dateFrom)
                ->whereDate('date', '<=', $dateTo)
                ->whereNotNull('check_in')
                ->whereNotNull('check_out')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, check_in, check_out)) as avg_min')
                ->value('avg_min');

            // Activity count from audit logs
            $activityCount = DB::table('activity_logs')
                ->where('user_id', $emp->user_id)
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->count();

            return [
                'id' => $emp->id,
                'name' => $emp->user?->name ?? 'Unknown',
                'job_title_en' => $emp->job_title_en,
                'job_title_ar' => $emp->job_title_ar,
                'department_en' => $emp->department?->name_en,
                'department_ar' => $emp->department?->name_ar,
                'employee_number' => $emp->employee_number,
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'overtime_hours' => round($overtimeHours, 1),
                'attendance_rate' => $attendanceRate,
                'avg_hours' => round($avgHours / 60, 1),
                'activity_count' => $activityCount,
            ];
        })->sortByDesc('attendance_rate')->values();

        // Aggregate stats
        $totalEmployees = $employees->count();
        $avgAttendanceRate = $staffKpis->where('total_days', '>', 0)->avg('attendance_rate');
        $totalOvertime = $staffKpis->sum('overtime_hours');
        $totalAbsent = $staffKpis->sum('absent_days');

        // Department breakdown
        $byDepartment = $staffKpis->groupBy('department_en')->map(function ($group, $dept) {
            return [
                'department_en' => $dept ?: 'Unassigned',
                'department_ar' => $group->first()['department_ar'] ?: 'غير محدد',
                'count' => $group->count(),
                'avg_attendance' => round($group->where('total_days', '>', 0)->avg('attendance_rate'), 1),
                'total_absent' => $group->sum('absent_days'),
                'total_overtime' => round($group->sum('overtime_hours'), 1),
            ];
        })->values();

        // Daily attendance trend
        $dailyTrend = Attendance::whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->select(
                DB::raw('DATE(date) as day'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('day')
            ->get();

        return Inertia::render('Admin/Reports/StaffPerformance', [
            'staff' => $staffKpis,
            'totals' => [
                'employees' => $totalEmployees,
                'avg_attendance' => round($avgAttendanceRate ?? 0, 1),
                'total_overtime' => round($totalOvertime, 1),
                'total_absent' => $totalAbsent,
            ],
            'byDepartment' => $byDepartment,
            'dailyTrend' => $dailyTrend,
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }
}

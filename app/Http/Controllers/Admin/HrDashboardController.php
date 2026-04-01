<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\SalarySlip;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HrDashboardController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();

        // Employee stats
        $employeeStats = [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'on_leave' => Employee::where('status', 'on_leave')->count(),
            'terminated' => Employee::where('status', 'terminated')->count(),
        ];

        // Department distribution
        $departmentDistribution = Department::withCount(['employees' => fn ($q) => $q->where('status', 'active')])
            ->active()
            ->ordered()
            ->get()
            ->map(fn ($d) => ['name' => $d->name_en, 'count' => $d->employees_count]);

        // Salary overview (current month)
        $salaryOverview = [
            'total' => SalarySlip::forPeriod($now->month, $now->year)->sum('net_salary'),
            'paid' => SalarySlip::forPeriod($now->month, $now->year)->paid()->sum('net_salary'),
            'pending' => SalarySlip::forPeriod($now->month, $now->year)->whereIn('status', ['draft', 'approved'])->sum('net_salary'),
            'slips_count' => SalarySlip::forPeriod($now->month, $now->year)->count(),
        ];

        // Today's attendance
        $today = $now->toDateString();
        $todayAttendance = [
            'present' => Attendance::where('date', $today)->where('status', 'present')->count(),
            'absent' => Attendance::where('date', $today)->where('status', 'absent')->count(),
            'late' => Attendance::where('date', $today)->where('status', 'late')->count(),
            'on_leave' => Attendance::where('date', $today)->where('status', 'leave')->count(),
        ];

        // Pending leave requests
        $pendingLeaves = Leave::where('status', 'pending')
            ->with('user:id,name')
            ->latest()
            ->limit(5)
            ->get();

        // Expiring contracts (next 30 days)
        $expiringContracts = Employee::expiringContracts(30)
            ->with(['user:id,name', 'department:id,name_en'])
            ->get();

        // Monthly payroll trend (last 6 months)
        $payrollTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $total = SalarySlip::forPeriod($date->month, $date->year)->sum('net_salary');
            $payrollTrend[] = [
                'label' => $date->format('M Y'),
                'total' => (float) $total,
            ];
        }

        // Pending advances count
        $pendingAdvances = Advance::pending()->count();

        return Inertia::render('Admin/HrDashboard/Index', [
            'employeeStats' => $employeeStats,
            'departmentDistribution' => $departmentDistribution,
            'salaryOverview' => $salaryOverview,
            'todayAttendance' => $todayAttendance,
            'pendingLeaves' => $pendingLeaves,
            'expiringContracts' => $expiringContracts,
            'payrollTrend' => $payrollTrend,
            'pendingAdvances' => $pendingAdvances,
        ]);
    }
}

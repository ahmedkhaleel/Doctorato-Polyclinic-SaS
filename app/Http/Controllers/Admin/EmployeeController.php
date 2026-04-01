<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::with(['user:id,name,email,is_active', 'department:id,name_ar,name_en']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('job_title_ar', 'like', "%{$search}%")
                    ->orWhere('job_title_en', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($department = $request->input('department_id')) {
            $query->where('department_id', $department);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees = $query->latest()->paginate(15)->withQueryString();

        $departments = Department::active()->ordered()->get(['id', 'name_ar', 'name_en']);

        // Summary stats
        $stats = [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'on_leave' => Employee::where('status', 'on_leave')->count(),
            'terminated' => Employee::where('status', 'terminated')->count(),
        ];

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $request->only(['search', 'department_id', 'status']),
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        $departments = Department::active()->ordered()->get(['id', 'name_ar', 'name_en']);

        // Users that don't have an employee record yet
        $availableUsers = User::active()
            ->whereDoesntHave('employee')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Employees/Create', [
            'departments' => $departments,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id|unique:employees,user_id',
            'department_id' => 'nullable|exists:departments,id',
            'job_title_ar' => 'nullable|string|max:255',
            'job_title_en' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'contract_type' => 'required|in:full_time,part_time,contract',
            'contract_end_date' => 'nullable|date|after:hire_date',
            'basic_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'national_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
        ]);

        $data['employee_number'] = Employee::generateEmployeeNumber();
        $data['status'] = 'active';

        // Default nullable numeric fields to 0 (DB columns are NOT NULL with default 0)
        foreach (['basic_salary', 'housing_allowance', 'transport_allowance', 'other_allowances'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        $employee = Employee::create($data);

        AuditLogger::log('created', $employee);

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee): Response
    {
        $employee->load(['user:id,name,email,is_active,role_id', 'user.role:id,display_name_en,display_name_ar', 'department:id,name_ar,name_en']);

        // Attendance summary for current month
        $currentMonth = Carbon::now();
        $attendanceSummary = [
            'present' => Attendance::where('user_id', $employee->user_id)
                ->whereMonth('date', $currentMonth->month)
                ->whereYear('date', $currentMonth->year)
                ->where('status', 'present')->count(),
            'absent' => Attendance::where('user_id', $employee->user_id)
                ->whereMonth('date', $currentMonth->month)
                ->whereYear('date', $currentMonth->year)
                ->where('status', 'absent')->count(),
            'late' => Attendance::where('user_id', $employee->user_id)
                ->whereMonth('date', $currentMonth->month)
                ->whereYear('date', $currentMonth->year)
                ->where('status', 'late')->count(),
            'overtime_hours' => Attendance::where('user_id', $employee->user_id)
                ->whereMonth('date', $currentMonth->month)
                ->whereYear('date', $currentMonth->year)
                ->sum('overtime_hours'),
        ];

        // Recent leaves
        $recentLeaves = Leave::where('user_id', $employee->user_id)
            ->latest()
            ->limit(5)
            ->get();

        // Recent attendance
        $recentAttendance = Attendance::where('user_id', $employee->user_id)
            ->latest('date')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Employees/Show', [
            'employee' => $employee,
            'attendanceSummary' => $attendanceSummary,
            'recentLeaves' => $recentLeaves,
            'recentAttendance' => $recentAttendance,
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $employee->load('user:id,name,email');

        $departments = Department::active()->ordered()->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Admin/Employees/Edit', [
            'employee' => $employee,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'job_title_ar' => 'nullable|string|max:255',
            'job_title_en' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'contract_type' => 'required|in:full_time,part_time,contract',
            'contract_end_date' => 'nullable|date|after:hire_date',
            'basic_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'national_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,on_leave,suspended,terminated',
            'termination_date' => 'nullable|date',
            'termination_reason' => 'nullable|string',
        ]);

        // Default nullable numeric fields to 0 (DB columns are NOT NULL with default 0)
        foreach (['basic_salary', 'housing_allowance', 'transport_allowance', 'other_allowances'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] ?? 0;
            }
        }

        $employee->update($data);

        AuditLogger::log('updated', $employee);

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->update([
            'status' => 'terminated',
            'termination_date' => now(),
        ]);

        AuditLogger::log('terminated', $employee);

        return redirect()->route('admin.employees.index')->with('success', 'Employee terminated successfully.');
    }
}

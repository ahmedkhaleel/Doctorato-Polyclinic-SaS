<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Employee;
use App\Models\SalarySlip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorSalarySlipController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $slips = collect();
        if ($employee) {
            $slips = SalarySlip::where('employee_id', $employee->id)
                ->where('status', 'paid')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(12);
        }

        return Inertia::render('Doctor/MySalarySlips/Index', [
            'slips' => $slips,
        ]);
    }

    public function show(Request $request, SalarySlip $salarySlip): Response
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee || $salarySlip->employee_id !== $employee->id) {
            abort(403, 'This salary slip does not belong to you.');
        }

        $salarySlip->load('employee.user:id,name,email', 'employee.department:id,name_en');

        return Inertia::render('Doctor/MySalarySlips/Show', [
            'slip' => $salarySlip,
        ]);
    }
}

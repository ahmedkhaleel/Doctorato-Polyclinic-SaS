<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalarySlip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service salary slip viewer for admin-panel employees (receptionist,
 * accountant, manager, etc.) — roles that use the admin panel but don't have
 * their own portal (doctor / secretary already have this there).
 *
 * Scoped strictly to the authenticated user's own employee record — no
 * employee-id parameter in the URL, no cross-user access.
 */
class AdminMyPayslipController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = Employee::where('user_id', auth()->id())->first();

        $slips = collect();
        if ($employee) {
            $slips = SalarySlip::where('employee_id', $employee->id)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(12);
        }

        return Inertia::render('Admin/MyPayslips/Index', [
            'slips'    => $slips,
            'employee' => $employee ? [
                'id'                 => $employee->id,
                'basic_salary'       => (float) $employee->basic_salary,
                'housing_allowance'  => (float) $employee->housing_allowance,
                'transport_allowance'=> (float) $employee->transport_allowance,
                'job_title'          => $employee->job_title,
            ] : null,
        ]);
    }

    public function show(SalarySlip $salarySlip): Response
    {
        $employee = Employee::where('user_id', auth()->id())->first();

        if (! $employee || $salarySlip->employee_id !== $employee->id) {
            abort(403, 'This salary slip does not belong to you.');
        }

        $salarySlip->load('employee.department');

        return Inertia::render('Admin/MyPayslips/Show', [
            'slip' => $salarySlip,
        ]);
    }
}

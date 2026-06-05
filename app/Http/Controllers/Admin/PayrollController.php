<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalarySlip;
use App\Services\AuditLogger;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $status = $request->input('status');

        $query = SalarySlip::with(['employee.user:id,name,email', 'employee.department:id,name_en'])
            ->forPeriod($month, $year);

        if ($status) {
            $query->where('status', $status);
        }

        $slips = $query->latest()->paginate(20)->withQueryString();

        // Summary
        $allSlips = SalarySlip::forPeriod($month, $year);
        $summary = [
            'total_slips' => (clone $allSlips)->count(),
            'total_earnings' => (clone $allSlips)->sum('total_earnings'),
            'total_deductions' => (clone $allSlips)->sum('total_deductions'),
            'total_net' => (clone $allSlips)->sum('net_salary'),
            'draft' => (clone $allSlips)->draft()->count(),
            'approved' => (clone $allSlips)->approved()->count(),
            'paid' => (clone $allSlips)->paid()->count(),
        ];

        return Inertia::render('Admin/Payroll/Index', [
            'slips' => $slips,
            'summary' => $summary,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Payroll/Create', [
            'currentMonth' => now()->month,
            'currentYear' => now()->year,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2024|max:2030',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;

        $payrollService = new PayrollService;
        $employees = Employee::active()->with('user')->get();
        $generated = 0;

        // Pre-load all employee IDs that already have slips for this period (avoids N+1)
        $existingSlipEmployeeIds = SalarySlip::forPeriod($month, $year)
            ->pluck('employee_id')
            ->toArray();

        foreach ($employees as $employee) {
            if (in_array($employee->id, $existingSlipEmployeeIds)) {
                continue;
            }

            $payrollService->generateForEmployee($employee, $month, $year);
            $generated++;
        }

        AuditLogger::log('generated_payroll', null, [
            'month' => $month,
            'year' => $year,
            'count' => $generated,
        ]);

        return redirect()->route('admin.payroll.index', ['month' => $month, 'year' => $year])
            ->with('success', "{$generated} salary slips generated successfully.");
    }

    public function show(SalarySlip $salarySlip): Response
    {
        $salarySlip->load([
            'employee.user:id,name,email',
            'employee.department:id,name_en',
            'approvedByUser:id,name',
            'paidByUser:id,name',
        ]);

        return Inertia::render('Admin/Payroll/Show', [
            'slip' => $salarySlip,
        ]);
    }

    public function approve(SalarySlip $salarySlip): RedirectResponse
    {
        if ($salarySlip->status !== 'draft') {
            return back()->with('error', 'Only draft slips can be approved.');
        }

        $salarySlip->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        AuditLogger::log('approved', $salarySlip);

        return back()->with('success', 'Salary slip approved.');
    }

    public function markPaid(Request $request, SalarySlip $salarySlip): RedirectResponse
    {
        if ($salarySlip->status !== 'approved') {
            return back()->with('error', 'Only approved slips can be marked as paid.');
        }

        $data = $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $salarySlip->update([
            'status' => 'paid',
            'paid_by' => auth()->id(),
            'paid_at' => now(),
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        // Record it in the Expense ledger so payroll appears in financial reports.
        app(\App\Services\LaborExpenseService::class)->recordForSalarySlip($salarySlip->fresh());

        AuditLogger::log('marked_paid', $salarySlip);

        return back()->with('success', 'Salary slip marked as paid.');
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:salary_slips,id']);

        $count = SalarySlip::whereIn('id', $request->ids)
            ->where('status', 'draft')
            ->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        AuditLogger::log('bulk_approved_payroll', null, ['count' => $count]);

        return back()->with('success', "{$count} salary slips approved.");
    }

    public function bulkMarkPaid(Request $request): RedirectResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:salary_slips,id']);

        // Iterate (not a bulk UPDATE) so each paid slip also posts to the
        // expense ledger via LaborExpenseService — matching single markPaid().
        // The bulk UPDATE previously skipped this, undercounting salary cost.
        $slips = SalarySlip::whereIn('id', $request->ids)->where('status', 'approved')->get();
        $count = 0;
        $labor = app(\App\Services\LaborExpenseService::class);

        foreach ($slips as $slip) {
            $slip->update(['status' => 'paid', 'paid_by' => auth()->id(), 'paid_at' => now()]);
            $labor->recordForSalarySlip($slip->fresh());
            $count++;
        }

        AuditLogger::log('bulk_marked_paid_payroll', null, ['count' => $count]);

        return back()->with('success', "{$count} salary slips marked as paid.");
    }

    public function print(SalarySlip $salarySlip): Response
    {
        $salarySlip->load([
            'employee.user:id,name,email',
            'employee.department:id,name_en',
        ]);

        return Inertia::render('Admin/Payroll/Print', [
            'slip' => $salarySlip,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdvanceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Advance::with(['employee.user:id,name', 'employee.department:id,name_en', 'approvedByUser:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $advances = $query->latest()->paginate(15)->withQueryString();

        $employees = Employee::active()->with('user:id,name')->get()->map(fn ($e) => [
            'id' => $e->id,
            'name' => $e->user->name ?? 'Unknown',
        ]);

        $stats = [
            'pending' => Advance::pending()->count(),
            'active' => Advance::active()->count(),
            'total_outstanding' => Advance::active()->sum('remaining_balance'),
        ];

        return Inertia::render('Admin/Advances/Index', [
            'advances' => $advances,
            'employees' => $employees,
            'filters' => $request->only('status'),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:1',
            'monthly_installment' => 'required|numeric|min:1',
            'reason' => 'nullable|string',
            'start_month' => 'nullable|integer|between:1,12',
            'start_year' => 'nullable|integer|min:2024',
        ]);

        $data['remaining_balance'] = $data['amount'];
        $data['total_paid'] = 0;
        $data['status'] = 'pending';
        $data['created_by'] = auth()->id();

        $advance = Advance::create($data);

        AuditLogger::log('created', $advance);

        return back()->with('success', 'Advance request created.');
    }

    public function approve(Advance $advance): RedirectResponse
    {
        if ($advance->status !== 'pending') {
            return back()->with('error', 'Only pending advances can be approved.');
        }

        $advance->update([
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        AuditLogger::log('approved', $advance);

        return back()->with('success', 'Advance approved and activated.');
    }

    public function reject(Advance $advance): RedirectResponse
    {
        if ($advance->status !== 'pending') {
            return back()->with('error', 'Only pending advances can be rejected.');
        }

        $advance->update(['status' => 'rejected']);

        AuditLogger::log('rejected', $advance);

        return back()->with('success', 'Advance rejected.');
    }
}

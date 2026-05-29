<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Leave::with(['user', 'approver']);

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($leaveType = $request->input('leave_type')) {
            $query->where('leave_type', $leaveType);
        }

        $leaves = $query->latest('start_date')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Leaves/Index', [
            'leaves' => $leaves,
            'filters' => $request->only(['user_id', 'status', 'leave_type']),
            'users' => User::active()->select('id', 'name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Leaves/Create', [
            'users' => User::active()->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|in:annual,sick,personal,unpaid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $data['status'] = 'pending';

        $leave = Leave::create($data);

        AuditLogger::log('created', $leave);

        return redirect()->route('admin.leaves.index')->with('success', 'Leave request created successfully.');
    }

    public function update(Request $request, Leave $leave): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'reason' => 'nullable|string',
        ]);

        // Set approver when approving or rejecting
        if (in_array($data['status'], ['approved', 'rejected'])) {
            $data['approved_by'] = auth()->id();
        }

        $previousStatus = $leave->status;
        $leave->update($data);
        $leave->refresh();

        AuditLogger::log('updated', $leave);

        // ─── Attendance sync ─────────────────────────────────────────────
        // On approval → write a 'leave' attendance row for every day in the
        // range (idempotent: updateOrCreate). This keeps the attendance
        // calendar accurate and ensures payroll can see leave days.
        // On rejection (if it was previously approved) → remove those rows
        // so the days revert to unrecorded (admin can enter actual status).
        if ($data['status'] === 'approved') {
            $this->syncLeaveAttendance($leave, create: true);
        } elseif ($previousStatus === 'approved' && $data['status'] === 'rejected') {
            $this->syncLeaveAttendance($leave, create: false);
        }

        $statusLabel = ucfirst($data['status']);

        return redirect()->back()->with('success', "Leave request {$statusLabel}.");
    }

    /**
     * Create or remove Attendance rows covering this leave's date range.
     * Only rows with status='leave' are ever removed — we never touch rows
     * the employee or admin wrote for another purpose.
     */
    private function syncLeaveAttendance(Leave $leave, bool $create): void
    {
        $userId = $leave->user_id;

        if ($create) {
            $current = $leave->start_date->copy();
            while ($current->lte($leave->end_date)) {
                Attendance::updateOrCreate(
                    ['user_id' => $userId, 'date' => $current->toDateString()],
                    [
                        'status'         => 'leave',
                        'check_in'       => null,
                        'check_out'      => null,
                        'overtime_hours' => 0,
                        'leave_id'       => $leave->id,
                        'notes'          => $leave->leave_type . ' leave',
                    ]
                );
                $current->addDay();
            }
        } else {
            // Delete only rows this specific leave created — safe even if
            // the employee had a manual attendance row for the same day.
            Attendance::where('leave_id', $leave->id)->delete();
        }
    }
}

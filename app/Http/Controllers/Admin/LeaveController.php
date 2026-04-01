<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use App\Services\AuditLogger;
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

        $leave->update($data);

        AuditLogger::log('updated', $leave);

        $statusLabel = ucfirst($data['status']);

        return redirect()->back()->with('success', "Leave request {$statusLabel}.");
    }
}

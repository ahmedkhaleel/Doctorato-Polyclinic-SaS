<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Leave;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorLeaveController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $leaves = Leave::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return Inertia::render('Doctor/MyLeaves/Index', [
            'leaves' => $leaves,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'leave_type' => 'required|in:annual,sick,personal,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';

        $leave = Leave::create($data);

        AuditLogger::log('created', $leave);

        return back()->with('success', $this->msg('Leave request submitted.', 'تم تقديم طلب الإجازة.'));
    }
}

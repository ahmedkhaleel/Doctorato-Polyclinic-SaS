<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Attendance::with('user');

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $attendances = $query->latest('date')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Attendances/Index', [
            'attendances' => $attendances,
            'filters' => $request->only(['user_id', 'status', 'date_from', 'date_to']),
            'users' => User::active()->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_in_lat' => 'nullable|numeric|between:-90,90',
            'check_in_lng' => 'nullable|numeric|between:-180,180',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'check_out_lat' => 'nullable|numeric|between:-90,90',
            'check_out_lng' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:present,absent,late,leave',
            'overtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $attendance = Attendance::create($data);

        AuditLogger::log('created', $attendance);

        return redirect()->back()->with('success', 'Attendance recorded successfully.');
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_in_lat' => 'nullable|numeric|between:-90,90',
            'check_in_lng' => 'nullable|numeric|between:-180,180',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'check_out_lat' => 'nullable|numeric|between:-90,90',
            'check_out_lng' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:present,absent,late,leave',
            'overtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $attendance->update($data);

        AuditLogger::log('updated', $attendance);

        return redirect()->back()->with('success', 'Attendance updated successfully.');
    }
}

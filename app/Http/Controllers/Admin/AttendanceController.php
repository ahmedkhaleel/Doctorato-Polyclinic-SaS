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
            'check_in_lat' => 'nullable|numeric',
            'check_in_lng' => 'nullable|numeric',
            'check_out' => 'nullable|date_format:H:i',
            'check_out_lat' => 'nullable|numeric',
            'check_out_lng' => 'nullable|numeric',
            'status' => 'required|in:present,absent,late,leave',
            'overtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Clean up nullable fields
        $data['overtime_hours'] = $data['overtime_hours'] ?? 0;
        $data['notes'] = $data['notes'] ?? '';

        // Remove null location fields to avoid issues if migration not run yet
        foreach (['check_in_lat', 'check_in_lng', 'check_out_lat', 'check_out_lng'] as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                unset($data[$field]);
            }
        }

        // Check if attendance already exists for this user on this date — update it instead
        $existing = Attendance::where('user_id', $data['user_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            $updateData = collect($data)->except(['user_id', 'date'])->toArray();
            $existing->update($updateData);
            AuditLogger::log('updated', $existing);
            return redirect()->back()->with('success', 'تم تحديث سجل الحضور الموجود بنجاح / Existing attendance updated successfully.');
        }

        $attendance = Attendance::create($data);

        AuditLogger::log('created', $attendance);

        return redirect()->back()->with('success', 'تم تسجيل الحضور بنجاح / Attendance recorded successfully.');
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_in_lat' => 'nullable|numeric',
            'check_in_lng' => 'nullable|numeric',
            'check_out' => 'nullable|date_format:H:i',
            'check_out_lat' => 'nullable|numeric',
            'check_out_lng' => 'nullable|numeric',
            'status' => 'required|in:present,absent,late,leave',
            'overtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Clean up nullable fields
        $data['overtime_hours'] = $data['overtime_hours'] ?? 0;
        $data['notes'] = $data['notes'] ?? '';

        // Remove null location fields to avoid issues if migration not run yet
        foreach (['check_in_lat', 'check_in_lng', 'check_out_lat', 'check_out_lng'] as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                unset($data[$field]);
            }
        }

        $attendance->update($data);

        AuditLogger::log('updated', $attendance);

        return redirect()->back()->with('success', 'Attendance updated successfully.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        AuditLogger::log('deleted', $attendance);

        $attendance->delete();

        return redirect()->back()->with('success', 'تم حذف سجل الحضور بنجاح / Attendance deleted successfully.');
    }
}

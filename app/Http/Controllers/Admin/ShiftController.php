<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function index(Request $request): Response
    {
        $shifts = Shift::withCount('employeeShifts')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Admin/Shifts/Index', [
            'shifts' => $shifts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $shift = Shift::create($data);

        AuditLogger::log('created', $shift);

        return redirect()->back()->with('success', 'Shift created successfully.');
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $shift->update($data);

        AuditLogger::log('updated', $shift);

        return redirect()->back()->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        AuditLogger::log('deleted', $shift);

        $shift->delete();

        return redirect()->back()->with('success', 'Shift deleted successfully.');
    }
}

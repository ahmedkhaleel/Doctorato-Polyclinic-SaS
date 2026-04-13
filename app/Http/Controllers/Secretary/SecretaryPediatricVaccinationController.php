<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\PediatricVaccination;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecretaryPediatricVaccinationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $vaccinations = PediatricVaccination::with('patient:id,full_name,date_of_birth,file_number,guardian_phone')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('file_number', 'like', "%{$search}%");
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderBy('scheduled_date')
            ->paginate(30)
            ->withQueryString();

        // Stats
        $stats = [
            'total' => PediatricVaccination::count(),
            'given' => PediatricVaccination::where('status', 'given')->count(),
            'overdue' => PediatricVaccination::where('status', 'scheduled')->where('scheduled_date', '<', today())->count(),
            'upcoming' => PediatricVaccination::where('status', 'scheduled')->whereBetween('scheduled_date', [today(), today()->addDays(7)])->count(),
        ];

        return Inertia::render('Secretary/Pediatric/Vaccinations/Index', [
            'vaccinations' => $vaccinations,
            'stats' => $stats,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function updateStatus(Request $request, PediatricVaccination $vaccination)
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,given,missed,postponed,contraindicated',
            'given_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $vaccination->update($validated);

        return redirect()->back()->with('success', 'Vaccination status updated');
    }
}

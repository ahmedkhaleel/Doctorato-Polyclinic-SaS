<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Medication::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%");
        }

        $medications = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Medications/Index', [
            'medications' => $medications,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:medications,name',
            'default_dosage' => 'nullable|string|max:255',
            'default_frequency' => 'nullable|string|max:255',
            'default_duration' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $medication = Medication::create($data);

        AuditLogger::log('created', $medication);

        return redirect()->back()->with('success', 'Medication added successfully.');
    }

    public function update(Request $request, Medication $medication): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:medications,name,' . $medication->id,
            'default_dosage' => 'nullable|string|max:255',
            'default_frequency' => 'nullable|string|max:255',
            'default_duration' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $medication->update($data);

        AuditLogger::log('updated', $medication);

        return redirect()->back()->with('success', 'Medication updated successfully.');
    }

    public function destroy(Medication $medication): RedirectResponse
    {
        AuditLogger::log('deleted', $medication);

        $medication->delete();

        return redirect()->back()->with('success', 'Medication deleted successfully.');
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        $medications = Medication::active()
            ->search($query)
            ->select('id', 'name', 'default_dosage', 'default_frequency', 'default_duration', 'category')
            ->limit(20)
            ->get();

        return response()->json($medications);
    }
}

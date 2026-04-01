<?php

namespace App\Http\Controllers\Patient;

use App\Models\DentalComparison;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientComparisonController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $query = DentalComparison::where('patient_id', $patientId)
            ->visibleToPatient()
            ->with('doctor:id,name_ar,name_en');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $comparisons = $query->latest()->paginate(12);

        return Inertia::render('Patient/Dental/Comparisons/Index', [
            'comparisons' => $comparisons,
            'categories' => DentalComparison::CATEGORIES,
            'activeCategory' => $request->get('category'),
        ]);
    }

    public function show(Request $request, string $locale, DentalComparison $comparison): Response
    {
        $this->authorizePatient($request, $comparison);

        if (!$comparison->is_visible_to_patient) {
            abort(403);
        }

        $comparison->load('doctor:id,name_ar,name_en');

        return Inertia::render('Patient/Dental/Comparisons/Show', [
            'comparison' => $comparison,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DermaSession;
use App\Models\Patient;
use App\Models\SkinCondition;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DermaPatientController extends Controller
{
    public function index(Request $request)
    {
        // A derma patient is anyone with a derma VISIT *or* a standalone
        // derma clinical record (skin condition / session) — sessions can be
        // logged without a visit, so visits alone undercount them.
        $patientIds = Visit::where('module', 'derma')->distinct()->pluck('patient_id')
            ->merge(SkinCondition::distinct()->pluck('patient_id'))
            ->merge(DermaSession::distinct()->pluck('patient_id'))
            ->filter()->unique()->values();

        $query = Patient::whereIn('id', $patientIds);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('file_number', 'like', "%$s%");
            });
        }

        $patients = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Derma/Patients', [
            'patients' => $patients,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Patient $patient)
    {
        return redirect()->route('admin.patients.show', ['patient' => $patient->id, 'tab' => 'derma']);
    }
}

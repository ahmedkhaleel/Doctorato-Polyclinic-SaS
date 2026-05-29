<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticSession;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CosmeticPatientController extends Controller
{
    public function index(Request $request)
    {
        // Cosmetic patients are those with cosmetic sessions (there is no
        // module='cosmetic' on visits — cosmetic lives under the derma module).
        $patientIds = CosmeticSession::distinct()->pluck('patient_id');
        $query = Patient::whereIn('id', $patientIds);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('file_number', 'like', "%$s%");
            });
        }

        return Inertia::render('Admin/Cosmetic/Patients', [
            'patients' => $query->orderByDesc('updated_at')->paginate(20)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Patient $patient)
    {
        return redirect()->route('admin.patients.show', ['patient' => $patient->id, 'tab' => 'cosmetic']);
    }
}

<?php

namespace App\Http\Controllers\Patient;

use App\Models\Prescription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientPrescriptionController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $prescriptions = Prescription::where('patient_id', $this->patientId($request))
            ->with(['doctor:id,name_en,name_ar', 'visit:id,visit_date'])
            ->latest('created_at')
            ->paginate(15);

        return Inertia::render('Patient/Prescriptions/Index', [
            'prescriptions' => $prescriptions,
        ]);
    }

    public function show(Request $request, string $locale, Prescription $prescription): Response
    {
        $this->authorizePatient($request, $prescription);

        $prescription->load([
            'doctor:id,name_en,name_ar,specialization_en,specialization_ar',
            'visit:id,visit_date',
            // medication is a string column (medication_name) on each item —
            // there is no related model to eager-load.
            'medications',
        ]);

        return Inertia::render('Patient/Prescriptions/Show', [
            'prescription' => $prescription,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Doctor;

use App\Models\MedicalDataAccessLog;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPatientController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = Patient::whereHas('visits', function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        })->withCount(['visits' => function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        }]);

        // Validate filter inputs
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Doctor/Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Request $request, Patient $patient): Response
    {
        $doctorId = $this->doctorId($request);

        // Verify this patient has had visits with this doctor
        $hasVisits = $patient->visits()->where('doctor_id', $doctorId)->exists();
        if (! $hasVisits) {
            abort(403, 'This patient is not in your records.');
        }

        $patient->load([
            'visits' => function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                    ->with(['service:id,name_en', 'prescriptions.items', 'photos'])
                    ->latest('visit_date');
            },
        ]);

        // Dental data (only if module enabled)
        $dentalData = null;
        if (\App\Services\ModuleManager::isEnabled('dental')) {
            $dentalData = [
                'charts' => $patient->dentalCharts()->orderBy('tooth_number')->get(),
                'treatments' => $patient->dentalTreatments()
                    ->where('doctor_id', $doctorId)
                    ->with('labOrder:id,dental_treatment_id,status,item_type')
                    ->latest()
                    ->take(15)
                    ->get(),
                'plans' => $patient->dentalTreatmentPlans()
                    ->where('doctor_id', $doctorId)
                    ->withCount('treatments')
                    ->latest()
                    ->take(10)
                    ->get(),
                'xrays' => $patient->dentalXrays()
                    ->where('doctor_id', $doctorId)
                    ->latest('taken_date')
                    ->take(8)
                    ->get(),
                'stats' => [
                    'total_treatments' => $patient->dentalTreatments()->where('doctor_id', $doctorId)->count(),
                    'completed_treatments' => $patient->dentalTreatments()->where('doctor_id', $doctorId)->where('status', 'completed')->count(),
                    'active_plans' => $patient->dentalTreatmentPlans()->where('doctor_id', $doctorId)->whereIn('status', ['approved', 'in_progress'])->count(),
                ],
            ];
        }

        // Add dental risk flags for doctor (safety-critical, always visible)
        // but medical history is redacted — doctors see flags, not details
        $dentalRiskFlags = [];
        $dentalMedicalHistory = null;
        if (\App\Services\ModuleManager::isEnabled('dental')) {
            $dentalRiskFlags = $patient->getDentalRiskFlags();
            $dentalMedicalHistory = $patient->getDentalMedicalHistory(false); // Redacted

            // Log medical data access
            MedicalDataAccessLog::record(
                $patient->id,
                MedicalDataAccessLog::ACCESS_VIEW,
                MedicalDataAccessLog::CATEGORY_RISK_FLAGS,
                ['risk_flags', 'dental_medical_redacted']
            );
        }

        return Inertia::render('Doctor/Patients/Show', [
            'patient' => $patient,
            'dentalData' => $dentalData,
            'dentalRiskFlags' => $dentalRiskFlags,
            'dentalMedicalHistory' => $dentalMedicalHistory,
        ]);
    }

    /**
     * Update doctor's personal notes on a patient.
     */
    public function updateNotes(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        // Verify this patient has had visits with this doctor
        $hasVisits = $patient->visits()->where('doctor_id', $doctorId)->exists();
        if (! $hasVisits) {
            abort(403, 'This patient is not in your records.');
        }

        $data = $request->validate([
            'doctor_notes' => 'nullable|string|max:5000',
        ]);

        $patient->update(['doctor_notes' => $data['doctor_notes']]);

        \App\Services\AuditLogger::log('updated_doctor_notes', $patient);

        return redirect()->back()->with('success', $this->msg('Notes updated.', 'تم تحديث الملاحظات.'));
    }
}

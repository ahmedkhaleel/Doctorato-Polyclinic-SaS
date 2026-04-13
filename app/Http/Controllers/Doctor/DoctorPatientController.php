<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DoctorFavoritePatient;
use App\Models\DoctorPatientNote;
use App\Models\MedicalDataAccessLog;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'last_visit_from' => 'nullable|date',
            'last_visit_to' => 'nullable|date',
            'service_id' => 'nullable|integer|exists:services,id',
            'favorites_only' => 'nullable|boolean',
        ]);

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            });
        }

        // Filter by last visit date range
        if ($lastVisitFrom = $filters['last_visit_from'] ?? null) {
            $query->whereHas('visits', function ($q) use ($doctorId, $lastVisitFrom) {
                $q->where('doctor_id', $doctorId)
                    ->where('visit_date', '>=', $lastVisitFrom);
            });
        }

        if ($lastVisitTo = $filters['last_visit_to'] ?? null) {
            $query->whereHas('visits', function ($q) use ($doctorId, $lastVisitTo) {
                $q->where('doctor_id', $doctorId)
                    ->where('visit_date', '<=', $lastVisitTo);
            });
        }

        // Filter by service
        if ($serviceId = $filters['service_id'] ?? null) {
            $query->whereHas('visits', function ($q) use ($doctorId, $serviceId) {
                $q->where('doctor_id', $doctorId)
                    ->where('service_id', $serviceId);
            });
        }

        // Filter favorites only
        if ($filters['favorites_only'] ?? false) {
            $query->whereHas('doctorFavorites', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            });
        }

        // Eager-load the doctor's quick notes and favorite status
        $query->with(['doctorNotes' => function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId)->orderByDesc('is_pinned')->latest();
        }]);

        // Add is_favorite attribute
        $query->withExists(['doctorFavorites as is_favorite' => function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        }]);

        $patients = $query->latest()->paginate(15)->withQueryString();

        // Services list for filter dropdown
        $services = Service::select('id', 'name_en', 'name_ar')
            ->orderBy('name_en')
            ->get();

        // Get favorite patient IDs for this doctor
        $favoriteIds = DoctorFavoritePatient::where('doctor_id', $doctorId)
            ->pluck('patient_id')
            ->toArray();

        return Inertia::render('Doctor/Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search', 'last_visit_from', 'last_visit_to', 'service_id', 'favorites_only']),
            'services' => $services,
            'favoriteIds' => $favoriteIds,
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
                    ->with(['service:id,name_en,name_ar', 'prescriptions.items', 'photos'])
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

        // Load quick notes and favorite status
        $quickNotes = DoctorPatientNote::where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        $isFavorite = DoctorFavoritePatient::where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->exists();

        return Inertia::render('Doctor/Patients/Show', [
            'patient' => $patient,
            'dentalData' => $dentalData,
            'dentalRiskFlags' => $dentalRiskFlags,
            'dentalMedicalHistory' => $dentalMedicalHistory,
            'quickNotes' => $quickNotes,
            'isFavorite' => $isFavorite,
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

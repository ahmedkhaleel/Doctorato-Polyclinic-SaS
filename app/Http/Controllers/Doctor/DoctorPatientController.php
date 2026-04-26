<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DoctorFavoritePatient;
use App\Models\DoctorPatientNote;
use App\Models\MedicalDataAccessLog;
use App\Models\Patient;
use App\Models\Service;
use App\Traits\BuildsPatientSpecialtyData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPatientController extends BaseDoctorController
{
    use BuildsPatientSpecialtyData;

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

        // Load ALL patient data (not only this doctor's)
        $patient->load([
            'visits' => fn ($q) => $q->with(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en', 'prescriptions.items', 'photos'])->latest('visit_date')->take(20),
            'invoices' => fn ($q) => $q->latest()->take(10),
            'prescriptions' => fn ($q) => $q->with(['doctor:id,name_ar,name_en', 'items'])->latest()->take(10),
            'photos' => fn ($q) => $q->latest()->take(20),
        ]);

        // Unified specialty detection
        $activeSpecialties = $patient->getActiveSpecialties();

        // Derma data (all — not filtered by doctor, as unified view)
        $dermaData = $this->buildDermaData($patient);

        // Dental data (only if module enabled) — show ALL dental data
        $dentalData = null;
        if (\App\Services\ModuleManager::isEnabled('dental')) {
            $dentalData = [
                'charts' => $patient->dentalCharts()->orderBy('tooth_number')->get(),
                'treatments' => $patient->dentalTreatments()
                    ->with(['doctor:id,name_ar,name_en', 'labOrder:id,dental_treatment_id,status,item_type'])
                    ->latest()
                    ->take(15)
                    ->get(),
                'plans' => $patient->dentalTreatmentPlans()
                    ->with('doctor:id,name_ar,name_en')
                    ->withCount('treatments')
                    ->latest()
                    ->take(10)
                    ->get(),
                'xrays' => $patient->dentalXrays()
                    ->with('doctor:id,name_ar,name_en')
                    ->latest('taken_date')
                    ->take(8)
                    ->get(),
                'labOrders' => $patient->dentalLabOrders()
                    ->with('doctor:id,name_ar,name_en')
                    ->latest('order_date')
                    ->take(10)
                    ->get(),
                'stats' => [
                    'total_treatments' => $patient->dentalTreatments()->count(),
                    'completed_treatments' => $patient->dentalTreatments()->where('status', 'completed')->count(),
                    'active_plans' => $patient->dentalTreatmentPlans()->whereIn('status', ['approved', 'in_progress'])->count(),
                    'pending_lab_orders' => $patient->dentalLabOrders()->whereIn('status', ['ordered', 'in_production'])->count(),
                ],
                'riskFlags' => $patient->getDentalRiskFlags(),
                'medicalHistory' => $patient->getDentalMedicalHistory(false),
                'canViewSensitive' => false,
                'canUpdateSensitive' => false,
            ];
        }

        // Pediatric data (only if module enabled AND patient appears pediatric)
        $pediatricData = null;
        $isPediatricPatient = $patient->guardian_name
            || ($patient->date_of_birth && \Carbon\Carbon::parse($patient->date_of_birth)->age < 18)
            || $patient->visits()->where('module', 'pediatric')->exists();

        if (\App\Services\ModuleManager::isEnabled('pediatric') && $isPediatricPatient) {
            $growthRecords = \App\Models\PediatricGrowthRecord::where('patient_id', $patient->id)
                ->orderBy('measurement_date')
                ->get();

            $vaccinations = \App\Models\PediatricVaccination::where('patient_id', $patient->id)
                ->orderBy('scheduled_date')
                ->get();

            $allergies = \App\Models\PediatricAllergy::where('patient_id', $patient->id)
                ->where('is_active', true)
                ->get();

            $pediatricData = [
                'is_pediatric' => true,
                'growthRecords' => $growthRecords,
                'vaccinations' => $vaccinations,
                'allergies' => $allergies,
                'stats' => [
                    'total_visits' => $patient->visits()->where('module', 'pediatric')->count(),
                    'growth_records' => $growthRecords->count(),
                    'total_vaccinations' => $vaccinations->count(),
                    'given_vaccinations' => $vaccinations->where('status', 'given')->count(),
                    'scheduled_vaccinations' => $vaccinations->where('status', 'scheduled')->count(),
                    'active_allergies' => $allergies->count(),
                    'latest_weight' => $growthRecords->last()?->weight_kg,
                    'latest_height' => $growthRecords->last()?->height_cm,
                    'latest_bmi' => $growthRecords->last()?->bmi,
                ],
            ];
        }

        // Financial summary (single aggregation)
        $invoiceAggregates = $patient->invoices()
            ->selectRaw('
                COALESCE(SUM(total), 0) as total_invoiced,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status IN ("unpaid","partial") THEN total - paid_amount ELSE 0 END), 0) as outstanding_balance
            ')
            ->first();

        $financialSummary = [
            'total_invoiced' => round((float) $invoiceAggregates->total_invoiced, 2),
            'total_paid' => round((float) $invoiceAggregates->total_paid, 2),
            'outstanding_balance' => round((float) $invoiceAggregates->outstanding_balance, 2),
            'total_visits' => $patient->visits()->count(),
            'completed_visits' => $patient->visits()->where('status', 'completed')->count(),
        ];

        // Dental risk flags (always visible) and redacted medical history
        $dentalRiskFlags = [];
        $dentalMedicalHistory = null;
        if (\App\Services\ModuleManager::isEnabled('dental')) {
            $dentalRiskFlags = $patient->getDentalRiskFlags();
            $dentalMedicalHistory = $patient->getDentalMedicalHistory(false);

            MedicalDataAccessLog::record(
                $patient->id,
                MedicalDataAccessLog::ACCESS_VIEW,
                MedicalDataAccessLog::CATEGORY_RISK_FLAGS,
                ['risk_flags', 'dental_medical_redacted']
            );
        }

        // Quick notes and favorite status
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
            'activeSpecialties' => $activeSpecialties,
            'dermaData' => $dermaData,
            'dentalData' => $dentalData,
            'pediatricData' => $pediatricData,
            'financialSummary' => $financialSummary,
            'dentalRiskFlags' => $dentalRiskFlags,
            'dentalMedicalHistory' => $dentalMedicalHistory,
            'quickNotes' => $quickNotes,
            'isFavorite' => $isFavorite,
            'engagement' => \App\Services\PatientEngagementService::forPatient($patient),
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

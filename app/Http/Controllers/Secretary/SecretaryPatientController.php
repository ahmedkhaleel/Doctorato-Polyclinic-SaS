<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Traits\BuildsPatientSpecialtyData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryPatientController extends BaseSecretaryController
{
    use BuildsPatientSpecialtyData;

    public function index(Request $request): Response
    {
        // Validate filter inputs
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $query = Patient::query();

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        }

        $patients = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Secretary/Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Secretary/Patients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/', Rule::unique('patients', 'phone')->whereNull('deleted_at')],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:100',
            'referral_source' => 'nullable|in:walk_in,social_media,google,friend,doctor,advertisement,other',
            'referred_by' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'phone.regex' => 'Phone must contain only numbers.',
            'phone2.regex' => 'Phone must contain only numbers.',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/patients', 'public');
        }

        $patient = new Patient($data);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        AuditLogger::log('created', $patient);

        \App\Events\PatientRegistered::dispatch($patient, 'secretary');

        return redirect()->route('secretary.patients.index')->with('success', $this->msg('Patient created successfully.', 'تم إنشاء المريض بنجاح.'));
    }

    public function show(Patient $patient): Response
    {
        $patient->load([
            'visits' => fn ($q) => $q->with(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en', 'photos'])->latest('visit_date')->take(20),
            'invoices' => fn ($q) => $q->latest()->take(10),
            'prescriptions' => fn ($q) => $q->with(['doctor:id,name_ar,name_en', 'items'])->latest()->take(10),
            'packageBundleBookings' => fn ($q) => $q->with([
                'packageBundle',
                'bundleServices' => fn ($s) => $s->with(['service', 'doctor']),
                'appointments' => fn ($a) => $a->with('doctor')->orderBy('appointment_date')->orderBy('start_time'),
            ])->latest()->take(10),
        ]);

        // Unified specialty detection
        $activeSpecialties = $patient->getActiveSpecialties();

        // Derma data
        $dermaData = $this->buildDermaData($patient);

        // Dental data (only if module enabled) — read-only for secretary
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

        // Pediatric data
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

        $financialSummary = [
            'total_invoiced' => round((float) $patient->invoices()->sum('total'), 2),
            'total_paid' => round((float) $patient->invoices()->sum('paid_amount'), 2),
            'outstanding_balance' => round((float) $patient->invoices()
                ->whereIn('status', ['unpaid', 'partial'])
                ->selectRaw('SUM(total - paid_amount) as balance')
                ->value('balance'), 2),
            'total_visits' => $patient->visits()->count(),
            'completed_visits' => $patient->visits()->where('status', 'completed')->count(),
        ];

        return Inertia::render('Secretary/Patients/Show', [
            'patient' => $patient,
            'financialSummary' => $financialSummary,
            'activeSpecialties' => $activeSpecialties,
            'dermaData' => $dermaData,
            'dentalData' => $dentalData,
            'pediatricData' => $pediatricData,
            'engagement' => \App\Services\PatientEngagementService::forPatient($patient),
            'doctors' => Doctor::select('id', 'name_en', 'name_ar')->where('status', 'active')->orderBy('name_en')->get(),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        return Inertia::render('Secretary/Patients/Edit', [
            'patient' => $patient,
        ]);
    }

    /**
     * Quick-create patient via AJAX (returns JSON) – used from Booking pages.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/', Rule::unique('patients', 'phone')->whereNull('deleted_at')],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:100',
            'referral_source' => 'nullable|in:walk_in,social_media,google,friend,doctor,advertisement,other',
            'referred_by' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string',
        ], [
            'phone.regex' => 'Phone must contain only numbers.',
            'phone2.regex' => 'Phone must contain only numbers.',
        ]);

        $patient = new Patient($data);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        AuditLogger::log('created', $patient, ['source' => 'quick_create']);

        return response()->json([
            'patient' => [
                'id' => $patient->id,
                'file_number' => $patient->file_number,
                'full_name' => $patient->full_name,
                'phone' => $patient->phone,
            ],
        ], 201);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/', Rule::unique('patients', 'phone')->ignore($patient->id)->whereNull('deleted_at')],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:100',
            'referral_source' => 'nullable|in:walk_in,social_media,google,friend,doctor,advertisement,other',
            'referred_by' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'phone.regex' => 'Phone must contain only numbers.',
            'phone2.regex' => 'Phone must contain only numbers.',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/patients', 'public');
        }

        $patient->update($data);

        AuditLogger::log('updated', $patient);

        return redirect()->route('secretary.patients.index')->with('success', $this->msg('Patient updated successfully.', 'تم تحديث بيانات المريض بنجاح.'));
    }

    /**
     * Search patients by name, phone, or file number (async autocomplete).
     */
    public function search(Request $request): JsonResponse
    {
        $search = trim($request->input('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $patients = Patient::where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            })
            ->select('id', 'full_name', 'phone', 'file_number')
            ->limit(20)
            ->get();

        return response()->json($patients);
    }
}

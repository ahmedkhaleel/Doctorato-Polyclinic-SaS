<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Doctor;
use App\Models\MedicalDataAccessLog;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /**
     * JSON search for patients (used by AJAX pickers).
     */
    public function search(Request $request)
    {
        $q = $request->input('q', '');
        $limit = min($request->input('limit', 10), 30);

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $patients = Patient::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('file_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->select('id', 'full_name', 'file_number', 'phone')
            ->limit($limit)
            ->get();

        return response()->json($patients);
    }

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

        return Inertia::render('Admin/Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Patients/Create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/patients', 'public');
        }

        $patient = new Patient($data);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        AuditLogger::log('created', $patient);

        \App\Events\PatientRegistered::dispatch($patient, 'admin');

        return redirect()->route('admin.patients.index')->with('success', 'Patient created successfully.');
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

    public function show(Patient $patient): Response
    {
        // Eager-load all relationships in a single call to avoid N+1 queries
        $patient->load([
            'visits' => fn ($q) => $q->with(['doctor', 'service'])->latest('visit_date')->take(20),
            'invoices' => fn ($q) => $q->latest()->take(10),
            'prescriptions' => fn ($q) => $q->with(['doctor', 'items'])->latest()->take(10),
            'photos' => fn ($q) => $q->latest()->take(20),
            'packageBundleBookings' => fn ($q) => $q->with([
                'packageBundle',
                'bundleServices' => fn ($s) => $s->with(['service', 'doctor']),
                'appointments' => fn ($a) => $a->with('doctor')->orderBy('appointment_date')->orderBy('start_time'),
            ])->latest()->take(10),
            'activeInsurance.company:id,name_ar,name_en,code',
            'activeInsurance.plan:id,name_ar,name_en,class,coverage_percentage',
        ]);

        // Dental data (only if module enabled)
        $dentalData = null;
        if (\App\Services\ModuleManager::isEnabled('dental')) {
            // Load dental relationships in batch
            $dentalTreatments = $patient->dentalTreatments()
                ->with(['doctor:id,name_ar,name_en', 'labOrder:id,dental_treatment_id,status,item_type'])
                ->latest()
                ->take(15)
                ->get();

            $dentalData = [
                'charts' => $patient->dentalCharts()->orderBy('tooth_number')->get(),
                'treatments' => $dentalTreatments,
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
                // Use single aggregation query instead of 4 separate COUNT queries
                'stats' => [
                    'total_treatments' => $patient->dentalTreatments()->count(),
                    'completed_treatments' => $patient->dentalTreatments()->where('status', 'completed')->count(),
                    'active_plans' => $patient->dentalTreatmentPlans()->whereIn('status', ['approved', 'in_progress'])->count(),
                    'pending_lab_orders' => $patient->dentalLabOrders()->whereIn('status', ['ordered', 'in_production'])->count(),
                ],
                'riskFlags' => $patient->getDentalRiskFlags(),
                'medicalHistory' => $patient->getDentalMedicalHistory(
                    auth()->user()?->can('patients.view_sensitive_medical') ?? false
                ),
                'canViewSensitive' => auth()->user()?->can('patients.view_sensitive_medical') ?? false,
                'canUpdateSensitive' => auth()->user()?->can('patients.update_sensitive_medical') ?? false,
            ];
        }

        // Pediatric data (only if module enabled AND patient appears to be pediatric)
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

        // Financial summary — use single aggregation query instead of 5 separate queries
        $invoiceAggregates = $patient->invoices()
            ->selectRaw('
                COALESCE(SUM(total), 0) as total_invoiced,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status IN ("unpaid","partial") THEN total - paid_amount ELSE 0 END), 0) as outstanding_balance
            ')
            ->first();

        $visitCounts = $patient->visits()
            ->selectRaw('
                COUNT(*) as total_visits,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_visits
            ')
            ->first();

        $financialSummary = [
            'total_invoiced' => round((float) $invoiceAggregates->total_invoiced, 2),
            'total_paid' => round((float) $invoiceAggregates->total_paid, 2),
            'outstanding_balance' => round((float) $invoiceAggregates->outstanding_balance, 2),
            'total_visits' => (int) $visitCounts->total_visits,
            'completed_visits' => (int) $visitCounts->completed_visits,
        ];

        // Latest vitals
        $latestVitals = $patient->vitals()
            ->with('recorder:id,name')
            ->orderByDesc('recorded_at')
            ->first();

        // Active insurance — already eager-loaded above
        $activeInsurance = $patient->activeInsurance;

        // Recent documents
        $recentDocuments = $patient->documents()
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Expiring documents
        $expiringDocs = $patient->documents()
            ->expiring(30)
            ->count();

        return Inertia::render('Admin/Patients/Show', [
            'patient' => $patient,
            'financialSummary' => $financialSummary,
            'dentalData' => $dentalData,
            'pediatricData' => $pediatricData,
            'latestVitals' => $latestVitals,
            'vitalsAlerts' => $latestVitals ? $latestVitals->getAlerts() : [],
            'activeInsurance' => $activeInsurance,
            'recentDocuments' => $recentDocuments,
            'expiringDocsCount' => $expiringDocs,
            'doctors' => Doctor::select('id', 'name_en', 'name_ar')->where('status', 'active')->orderBy('name_en')->get(),
        ]);
    }

    public function timeline(Patient $patient): Response
    {
        // Eager-load ALL timeline relationships in a single batch to avoid N+1 queries
        $patient->load([
            'visits.doctor:id,name_en,name_ar',
            'visits.service:id,name_en,name_ar',
            'invoices',
            'prescriptions.doctor:id,name_en,name_ar',
            'vitals.recorder:id,name',
            'documents',
            'referrals.referringDoctor:id,name',
            'referrals.referredToDoctor:id,name',
        ]);

        // Gather all events into a unified timeline (no extra queries — all pre-loaded)
        $events = collect();

        // Visits
        $patient->visits->each(function ($v) use ($events) {
            $events->push([
                'type' => 'visit',
                'date' => $v->visit_date ?? $v->created_at->toDateString(),
                'time' => $v->created_at->format('H:i'),
                'title_en' => 'Visit: ' . ($v->service?->name_en ?? $v->visit_type),
                'title_ar' => 'زيارة: ' . ($v->service?->name_ar ?? $v->visit_type),
                'subtitle' => $v->doctor?->name_en,
                'status' => $v->status,
                'details' => $v->diagnosis,
                'id' => $v->id,
                'url' => "/admin/visits/{$v->id}",
            ]);
        });

        // Invoices
        $patient->invoices->each(function ($i) use ($events) {
            $events->push([
                'type' => 'invoice',
                'date' => $i->invoice_date ?? $i->created_at->toDateString(),
                'time' => $i->created_at->format('H:i'),
                'title_en' => "Invoice #{$i->invoice_number}",
                'title_ar' => "فاتورة #{$i->invoice_number}",
                'subtitle' => number_format($i->total, 2) . ' - ' . $i->status,
                'status' => $i->status,
                'id' => $i->id,
                'url' => "/admin/invoices/{$i->id}",
            ]);
        });

        // Prescriptions
        $patient->prescriptions->each(function ($p) use ($events) {
            $events->push([
                'type' => 'prescription',
                'date' => $p->created_at->toDateString(),
                'time' => $p->created_at->format('H:i'),
                'title_en' => 'Prescription',
                'title_ar' => 'وصفة طبية',
                'subtitle' => $p->doctor?->name_en,
                'details' => $p->diagnosis,
                'status' => $p->status ?? 'issued',
                'id' => $p->id,
            ]);
        });

        // Vitals
        $patient->vitals->each(function ($v) use ($events) {
            $bpLabel = $v->bp_systolic ? "{$v->bp_systolic}/{$v->bp_diastolic}" : null;
            $events->push([
                'type' => 'vital',
                'date' => $v->recorded_at?->toDateString() ?? $v->created_at->toDateString(),
                'time' => ($v->recorded_at ?? $v->created_at)->format('H:i'),
                'title_en' => 'Vitals Recorded',
                'title_ar' => 'تسجيل علامات حيوية',
                'subtitle' => collect([
                    $bpLabel ? "BP: {$bpLabel}" : null,
                    $v->heart_rate ? "HR: {$v->heart_rate}" : null,
                    $v->temperature ? "Temp: {$v->temperature}°C" : null,
                ])->filter()->implode(' · '),
                'status' => 'recorded',
                'id' => $v->id,
            ]);
        });

        // Documents
        $patient->documents->each(function ($d) use ($events) {
            $events->push([
                'type' => 'document',
                'date' => $d->document_date ?? $d->created_at->toDateString(),
                'time' => $d->created_at->format('H:i'),
                'title_en' => 'Document: ' . $d->title,
                'title_ar' => 'مستند: ' . $d->title,
                'subtitle' => $d->type_label,
                'status' => $d->is_expired ? 'expired' : 'active',
                'id' => $d->id,
            ]);
        });

        // Referrals — use pre-loaded relationship instead of direct model query
        $patient->referrals->each(function ($r) use ($events) {
            $events->push([
                'type' => 'referral',
                'date' => $r->created_at->toDateString(),
                'time' => $r->created_at->format('H:i'),
                'title_en' => "Referral: {$r->from_department} → {$r->to_department}",
                'title_ar' => "تحويل: {$r->from_department} → {$r->to_department}",
                'subtitle' => $r->referringDoctor?->name . ' → ' . ($r->referredToDoctor?->name ?? '?'),
                'details' => $r->reason,
                'status' => $r->status,
                'id' => $r->id,
            ]);
        });

        // Sort by date descending
        $timeline = $events->sortByDesc(fn ($e) => $e['date'] . ' ' . $e['time'])->values();

        return Inertia::render('Admin/Patients/Timeline', [
            'patient' => $patient->only('id', 'full_name', 'phone', 'file_number', 'gender', 'date_of_birth'),
            'timeline' => $timeline,
        ]);
    }

    public function edit(Patient $patient): Response
    {
        return Inertia::render('Admin/Patients/Edit', [
            'patient' => $patient,
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/patients', 'public');
        }

        $patient->update($data);

        AuditLogger::log('updated', $patient);

        return redirect()->route('admin.patients.index')->with('success', 'Patient updated successfully.');
    }

    public function updateDentalMedical(Request $request, Patient $patient): RedirectResponse
    {
        // Sensitive fields require elevated permission
        $sensitiveFields = Patient::SENSITIVE_MEDICAL_FIELDS;
        $hasSensitiveChanges = collect($request->keys())->intersect($sensitiveFields)->isNotEmpty();

        if ($hasSensitiveChanges && !$request->user()->can('patients.update_sensitive_medical')) {
            abort(403, 'You do not have permission to update sensitive medical data.');
        }

        $data = $request->validate([
            'has_dental_anxiety' => 'boolean',
            'dental_anxiety_level' => 'nullable|in:none,mild,moderate,severe',
            'previous_dental_surgeries' => 'nullable|string|max:5000',
            'latex_allergy' => 'boolean',
            'anesthesia_complications' => 'boolean',
            'anesthesia_notes' => 'nullable|string|max:5000',
            'is_pregnant' => 'boolean',
            'is_breastfeeding' => 'boolean',
            'has_bleeding_disorder' => 'boolean',
            'takes_blood_thinners' => 'boolean',
            'blood_thinner_name' => 'nullable|string|max:255',
            'has_heart_condition' => 'boolean',
            'has_diabetes' => 'boolean',
            'diabetes_type' => 'nullable|in:type_1,type_2,gestational',
            'has_hepatitis' => 'boolean',
            'hepatitis_type' => 'nullable|in:A,B,C',
            'has_hiv' => 'boolean',
            'is_smoker' => 'boolean',
            'smoking_frequency' => 'nullable|in:light,moderate,heavy,former',
            'jaw_problems' => 'boolean',
            'teeth_grinding' => 'boolean',
            'last_dental_visit' => 'nullable|date|before_or_equal:today',
            'dental_medical_notes' => 'nullable|string|max:5000',
        ]);

        $patient->update($data);

        // Log medical data access
        MedicalDataAccessLog::logMedicalUpdate($patient->id, array_keys($data));

        AuditLogger::log('updated', $patient, [
            'section' => 'dental_medical_history',
            'fields_updated' => array_keys($data),
        ], "Updated dental medical history for \"{$patient->full_name}\"");

        return redirect()->back()->with('success', 'Dental medical history updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        AuditLogger::log('deleted', $patient);

        \Illuminate\Support\Facades\DB::transaction(function () use ($patient) {
            // Clean up the linked User account if one exists
            if ($patient->user_id && $linkedUser = \App\Models\User::find($patient->user_id)) {
                $linkedUser->delete();
            }

            $patient->delete();
        });

        return redirect()->route('admin.patients.index')->with('success', 'Patient deleted successfully.');
    }
}

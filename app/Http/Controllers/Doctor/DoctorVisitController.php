<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\VisitWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorVisitController extends BaseDoctorController
{
    public function __construct(
        protected VisitWorkflowService $workflowService,
    ) {}

    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = Visit::with([
            'patient:id,full_name,file_number,phone',
            'service:id,name_en,name_ar',
            'packageBundleBooking:id,booking_number,package_bundle_id',
            'packageBundleBooking.packageBundle:id,name_ar,name_en',
        ])
            ->where('doctor_id', $doctorId);

        // Validate filter inputs
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:waiting,in_progress,completed,cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'module' => 'nullable|string|max:50',
        ]);

        if ($module = $filters['module'] ?? null) {
            $query->where('module', $module);
        }

        if ($search = $filters['search'] ?? null) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($dateFrom = $filters['date_from'] ?? null) {
            $query->whereDate('visit_date', '>=', $dateFrom);
        }

        if ($dateTo = $filters['date_to'] ?? null) {
            $query->whereDate('visit_date', '<=', $dateTo);
        }

        $visits = $query->latest('visit_date')->paginate(15)->withQueryString();

        return Inertia::render('Doctor/Visits/Index', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'module']),
        ]);
    }

    public function show(Request $request, Visit $visit): Response
    {
        $this->authorizeDoctor($request, $visit);

        $visit->load([
            'patient.activeInsurance.company:id,name_ar,name_en',
            'service',
            'prescriptions.items',
            'prescriptions.doctor',
            'invoice.payments.paymentMethod',
            'photos',
            'dentalTreatments',
        ]);

        $extra = [];

        // Load dental data for dental visits — use patient relationship to avoid N+1
        if ($visit->module === 'dental' && $visit->patient_id) {
            $patient = $visit->patient;
            $extra['dentalChart'] = $patient->dentalCharts()
                ->orderBy('tooth_number')
                ->get()
                ->keyBy('tooth_number');
            $extra['dentalXrays'] = $patient->dentalXrays()
                ->latest('taken_date')
                ->limit(10)
                ->get();
            $extra['dentalConditions'] = \App\Models\DentalChart::CONDITIONS;
            $extra['dentalSurfaces'] = \App\Models\DentalChart::SURFACES;
            $extra['allTeeth'] = \App\Models\DentalChart::ALL_TEETH;
            $extra['treatmentTypes'] = \App\Models\DentalTreatment::TYPES;

            // Dental risk flags for medical alerts
            $patient = $visit->patient;
            if ($patient) {
                $extra['perioSummary'] = \App\Models\PerioMeasurement::summaryFor($visit->patient_id);
                $extra['dentalRiskFlags'] = $patient->getDentalRiskFlags();
                $extra['dentalMedicalInfo'] = $patient->only([
                    'has_dental_anxiety', 'dental_anxiety_level',
                    'is_pregnant', 'is_breastfeeding',
                    'has_bleeding_disorder', 'takes_blood_thinners', 'blood_thinner_name',
                    'has_heart_condition', 'has_diabetes', 'diabetes_type',
                    'has_hepatitis', 'hepatitis_type', 'has_hiv',
                    'latex_allergy', 'anesthesia_complications',
                    'is_smoker', 'smoking_frequency',
                    'allergies', 'chronic_conditions', 'current_medications', 'blood_type',
                ]);
            }
        }

        // ── Derma: active treatment course + session log ─────
        if ($visit->module === 'derma' && $visit->patient_id) {
            $activePlan = \App\Models\DermaTreatmentPlan::where('patient_id', $visit->patient_id)
                ->active()
                ->latest('start_date')
                ->first();

            if ($activePlan) {
                $extra['dermaActivePlan'] = array_merge(
                    $activePlan->only([
                        'id', 'title_ar', 'title_en', 'session_type', 'estimated_sessions',
                        'completed_sessions', 'interval_days', 'status', 'start_date',
                    ]),
                    [
                        'progress_percentage' => $activePlan->progress_percentage,
                        'sessions_remaining' => $activePlan->sessions_remaining,
                    ]
                );
            }

            $extra['dermaSessions'] = \App\Models\DermaSession::where('patient_id', $visit->patient_id)
                ->orderByRaw('COALESCE(completed_at, created_at) DESC')
                ->limit(8)
                ->get([
                    'id', 'visit_id', 'session_type', 'area_treated', 'product_used',
                    'session_number', 'total_sessions', 'cost', 'completed_at', 'next_session_date',
                ]);
            $extra['dermaLesions'] = \App\Models\DermaLesion::where('patient_id', $visit->patient_id)
                ->get(['id', 'view', 'x', 'y', 'lesion_type', 'size_mm', 'note']);
        }

        // ── OB/GYN: active pregnancy (with gestational age) + labs ──
        if ($visit->module === 'obgyn' && $visit->patient_id) {
            $pregnancy = \App\Models\Pregnancy::where('patient_id', $visit->patient_id)
                ->where('status', \App\Models\Pregnancy::STATUS_ACTIVE)
                ->latest('lmp')
                ->first();

            if ($pregnancy) {
                $days = $pregnancy->lmp ? (int) $pregnancy->lmp->diffInDays(now()) : null;
                $extra['obgynPregnancy'] = array_merge(
                    $pregnancy->only([
                        'id', 'lmp', 'edd', 'gravida', 'para', 'blood_group', 'rh_factor',
                        'is_high_risk', 'risk_factors', 'conception_method', 'status',
                    ]),
                    [
                        'gestational_weeks' => $days !== null ? intdiv($days, 7) : null,
                        'gestational_days' => $days !== null ? $days % 7 : null,
                    ]
                );

                $extra['obgynLabTests'] = $pregnancy->labTests()
                    ->latest('result_date')
                    ->limit(6)
                    ->get(['id', 'test_type', 'value', 'unit', 'reference_range', 'result_date', 'is_abnormal']);
            }
        }

        // ── Physiotherapy: active plan + sessions + latest assessment (ROM/MMT/pain) ──
        if ($visit->module === 'physiotherapy' && $visit->patient_id) {
            $this->loadPhysioExtras($visit, $extra);
        }

        // ── Psychiatry / Neurology: encounter + scale trend + meds + (gated) risk ──
        if (in_array($visit->module, ['psychiatry', 'neurology'], true) && $visit->patient_id) {
            $module = $visit->module;

            $encounter = \App\Models\NeuropsychEncounter::where('patient_id', $visit->patient_id)
                ->where('module', $module)
                ->where('visit_id', $visit->id)
                ->first()
                ?? \App\Models\NeuropsychEncounter::where('patient_id', $visit->patient_id)
                    ->where('module', $module)
                    ->latest('encounter_date')
                    ->first();

            if ($encounter) {
                $extra['neuroEncounter'] = $encounter->only([
                    'id', 'note_format', 'subjective', 'objective', 'assessment', 'plan', 'mse', 'encounter_date', 'visit_id',
                ]);
            }

            // Scale results (newest first) — feed the trend sparkline + latest chip.
            $extra['neuroScales'] = \App\Models\ScaleResult::where('patient_id', $visit->patient_id)
                ->latest('taken_at')
                ->limit(12)
                ->get(['id', 'scale_key', 'score', 'severity', 'flag', 'taken_at']);

            // Active medications in this module.
            $extra['neuroMeds'] = \App\Models\MedicationPlan::where('patient_id', $visit->patient_id)
                ->where('module', $module)
                ->whereNull('stopped_at')
                ->latest('started_at')
                ->get(['id', 'drug', 'drug_class', 'dose', 'frequency', 'route', 'is_controlled', 'started_at']);

            // Symptom diaries (last 90d) for the seizure heatmap + headache trend.
            $extra['neuroSeizures'] = \App\Models\SeizureDiaryEntry::where('patient_id', $visit->patient_id)
                ->where('occurred_at', '>=', now()->subDays(90))
                ->orderBy('occurred_at')
                ->get(['id', 'occurred_at', 'seizure_type', 'duration_seconds']);
            $extra['neuroHeadaches'] = \App\Models\HeadacheDiaryEntry::where('patient_id', $visit->patient_id)
                ->whereDate('date', '>=', now()->subDays(90)->toDateString())
                ->orderBy('date')
                ->get(['id', 'date', 'intensity', 'ichd3_type', 'aura']);

            // Risk assessment is SENSITIVE: gated by {module}.view_sensitive + audited.
            $canSensitive = (bool) ($request->user()?->role?->hasPermission("{$module}.view_sensitive") ?? false);
            $extra['neuroCanViewSensitive'] = $canSensitive;
            if ($canSensitive) {
                $risk = \App\Models\RiskAssessment::where('patient_id', $visit->patient_id)
                    ->where('is_active', true)
                    ->latest('assessed_at')
                    ->first();
                if ($risk) {
                    $extra['neuroRisk'] = $risk->only(['id', 'type', 'tool', 'risk_level', 'assessed_at']);
                    \App\Models\MedicalDataAccessLog::record(
                        $visit->patient_id,
                        'view',
                        'neuropsych_risk',
                        ['risk_level', 'type'],
                        'Visit page risk summary'
                    );
                }
            }
        }

        // ── Patient Vitals ──────────────────────────────────
        if ($visit->patient_id) {
            $latestVitals = $visit->patient->vitals()
                ->orderByDesc('recorded_at')
                ->first();
            $extra['latestVitals'] = $latestVitals;
            $extra['vitalsAlerts'] = $latestVitals ? $latestVitals->getAlerts() : [];

            // Vitals history for trend charts (last 10 readings, oldest first)
            $extra['vitalsHistory'] = $visit->patient->vitals()
                ->orderByDesc('recorded_at')
                ->limit(10)
                ->get()
                ->reverse()
                ->values()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'visit_id' => $v->visit_id,
                    'date' => $v->recorded_at?->format('Y-m-d'),
                    'time' => $v->recorded_at?->format('H:i'),
                    'date_label' => $v->recorded_at?->format('d/m'),
                    'bp_sys' => $v->blood_pressure_systolic ? (float) $v->blood_pressure_systolic : null,
                    'bp_dia' => $v->blood_pressure_diastolic ? (float) $v->blood_pressure_diastolic : null,
                    'hr' => $v->heart_rate ? (float) $v->heart_rate : null,
                    'temp' => $v->temperature ? (float) $v->temperature : null,
                    'spo2' => $v->oxygen_saturation ? (float) $v->oxygen_saturation : null,
                    'rr' => $v->respiratory_rate ? (float) $v->respiratory_rate : null,
                    'weight' => $v->weight ? (float) $v->weight : null,
                    'height' => $v->height ? (float) $v->height : null,
                    'bmi' => $v->bmi ? (float) $v->bmi : null,
                    'sugar' => $v->blood_sugar ? (float) $v->blood_sugar : null,
                    'pain' => $v->pain_level,
                    'is_current' => $v->visit_id === $visit->id,
                ]);

            // Active insurance — already eager-loaded with patient above
            $extra['activeInsurance'] = $visit->patient->activeInsurance;
        }

        return Inertia::render('Doctor/Visits/Show', array_merge([
            'visit' => $visit,
        ], $extra));
    }

    /**
     * Physiotherapy visit extras: active plan progress, recent sessions, and the
     * latest assessment's ROM / MMT / pain-map rows. Lean column sets, N+1-safe.
     */
    private function loadPhysioExtras(Visit $visit, array &$extra): void
    {
        $plan = \App\Models\PhysioTreatmentPlan::where('patient_id', $visit->patient_id)
            ->active()->latest('start_date')->first();
        if ($plan) {
            $extra['physioActivePlan'] = array_merge(
                $plan->only(['id', 'title_ar', 'title_en', 'frequency', 'estimated_sessions', 'completed_sessions', 'status', 'start_date']),
                ['progress_percentage' => $plan->progress_percentage, 'sessions_remaining' => $plan->sessions_remaining]
            );
        }

        $extra['physioSessions'] = \App\Models\PhysioSession::where('patient_id', $visit->patient_id)
            ->latest('session_date')->limit(10)
            ->get(['id', 'session_number', 'session_date', 'attended', 'pain_before', 'pain_after', 'cost']);

        $assessment = \App\Models\PhysioAssessment::where('patient_id', $visit->patient_id)
            ->with(['romMeasurements', 'strengthTests', 'painPoints'])
            ->latest('assessment_date')->first();
        if ($assessment) {
            $extra['physioAssessment'] = $assessment->only(['id', 'assessment_date', 'diagnosis']);
            $extra['physioRom'] = $assessment->romMeasurements->map->only(['joint', 'motion', 'side', 'arom', 'prom', 'normal_ref'])->values();
            $extra['physioStrength'] = $assessment->strengthTests->map->only(['muscle_group', 'side', 'grade'])->values();
            $extra['physioPainPoints'] = $assessment->painPoints->map->only(['view', 'x', 'y', 'intensity', 'pain_type'])->values();
        }
    }

    public function start(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $this->workflowService->start($visit);

        AuditLogger::log('started', $visit);

        return redirect()->back()->with('success', $this->msg('Visit started.', 'تم بدء الزيارة.'));
    }

    public function complete(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $results = $this->workflowService->complete($visit);

        AuditLogger::log('completed', $visit);

        $message = app()->getLocale() === 'ar' ? 'تم إكمال الزيارة.' : 'Visit completed.';
        if ($results['invoice']) {
            $message .= app()->getLocale() === 'ar'
                ? ' فاتورة رقم #'.$results['invoice']->invoice_number.' تم إنشاؤها.'
                : ' Invoice #'.$results['invoice']->invoice_number.' generated.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function cancel(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        if (! in_array($visit->status, ['waiting', 'in_progress'])) {
            return redirect()->back()->with('error', $this->msg('Only waiting or in-progress visits can be cancelled.', 'يمكن إلغاء الزيارات في حالة الانتظار أو قيد التنفيذ فقط.'));
        }

        $visit->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        AuditLogger::log('cancelled', $visit);

        return redirect()->back()->with('success', $this->msg('Visit cancelled.', 'تم إلغاء الزيارة.'));
    }

    public function updateDiagnosis(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $data = $request->validate([
            'diagnosis' => 'nullable|string|max:5000',
            'doctor_notes' => 'nullable|string|max:5000',
        ]);

        $visit->update($data);

        AuditLogger::log('updated_diagnosis', $visit);

        return redirect()->back()->with('success', $this->msg('Diagnosis updated.', 'تم تحديث التشخيص.'));
    }

    public function uploadPhoto(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'caption' => 'nullable|string|max:255',
            'type' => 'nullable|in:before,after,during',
        ]);

        $file = $request->file('photo');
        // Sanitize filename to prevent path traversal
        $safeName = preg_replace('/[^\w\s\-\.]/', '_', basename($file->getClientOriginalName()));
        $path = $file->store('visit-photos', 'local');

        $photo = $visit->photos()->create([
            'photo_path' => $path,
            'caption' => $request->input('caption'),
            'photo_type' => $request->input('type', 'during'),
        ]);

        AuditLogger::log('photo_uploaded', $visit, [
            'photo_id' => $photo->id,
            'photo_type' => $request->input('type', 'during'),
        ]);

        return redirect()->back()->with('success', $this->msg('Photo uploaded.', 'تم رفع الصورة.'));
    }

    /**
     * Store vital signs for a patient (from doctor panel).
     */
    public function storeVitals(Request $request, \App\Models\Patient $patient)
    {
        // Convert empty strings to null before validation
        $input = collect($request->all())->map(fn ($v) => $v === '' ? null : $v)->toArray();
        $request->merge($input);

        $request->validate([
            'bp_systolic' => 'nullable|numeric|min:50|max:300',
            'bp_diastolic' => 'nullable|numeric|min:30|max:200',
            'heart_rate' => 'nullable|numeric|min:30|max:250',
            'temperature' => 'nullable|numeric|min:34|max:43',
            'respiratory_rate' => 'nullable|numeric|min:5|max:60',
            'spo2' => 'nullable|numeric|min:50|max:100',
            'weight' => 'nullable|numeric|min:1|max:500',
            'height' => 'nullable|numeric|min:30|max:300',
            'blood_sugar' => 'nullable|numeric|min:20|max:800',
            'pain_level' => 'nullable|integer|min:0|max:10',
        ]);

        // Map form field names to database column names
        $data = [
            'patient_id' => $patient->id,
            'visit_id' => $request->input('visit_id') ?: null,
            'blood_pressure_systolic' => $request->input('bp_systolic'),
            'blood_pressure_diastolic' => $request->input('bp_diastolic'),
            'heart_rate' => $request->input('heart_rate'),
            'temperature' => $request->input('temperature'),
            'respiratory_rate' => $request->input('respiratory_rate'),
            'oxygen_saturation' => $request->input('spo2'),
            'weight' => $request->input('weight'),
            'height' => $request->input('height'),
            'blood_sugar' => $request->input('blood_sugar'),
            'pain_level' => $request->input('pain_level'),
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
            'source' => 'manual',
        ];

        $vital = \App\Models\PatientVital::create($data);

        AuditLogger::log('vitals_recorded', $vital, [
            'patient_id' => $patient->id,
            'visit_id' => $data['visit_id'],
        ]);

        return response()->json([
            'message' => 'Vitals recorded successfully',
            'vital' => $vital,
            'alerts' => $vital->getAlerts(),
        ]);
    }
}

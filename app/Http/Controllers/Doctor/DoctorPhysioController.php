<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Patient;
use App\Models\PhysioAssessment;
use App\Models\PhysioSession;
use App\Models\PhysioTreatmentPlan;
use App\Services\AuditLogger;
use App\Services\Physio\RomNormatives;
use App\Services\PhysioBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Doctor-side physiotherapy cockpit: dashboard, patient file, assessment
 * capture (ROM goniometry + MMT 0–5 + body-map pain points), plan of care
 * (SMART goals + modality dosage), and the billable treatment session log.
 *
 * Mirrors DoctorObgynController: patient-scoped, N+1-safe, ownership-guarded.
 * Billing/commission goes through PhysioBillingService + the unified
 * PricingResolver/CommissionCalculator (physiotherapy already wired in).
 */
class DoctorPhysioController extends BaseDoctorController
{
    public function __construct(private PhysioBillingService $billing) {}

    /** KPIs + recent sessions + plans nearing completion. */
    public function dashboard(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $activePlans = PhysioTreatmentPlan::where('doctor_id', $doctorId)->active()->count();
        $sessionsThisMonth = PhysioSession::where('doctor_id', $doctorId)
            ->whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->count();
        $assessmentsThisMonth = PhysioAssessment::where('doctor_id', $doctorId)
            ->whereMonth('assessment_date', now()->month)
            ->whereYear('assessment_date', now()->year)
            ->count();

        $recentSessions = PhysioSession::where('doctor_id', $doctorId)
            ->with('patient:id,full_name,photo')
            ->latest('session_date')
            ->limit(10)
            ->get(['id', 'patient_id', 'session_date', 'session_number', 'pain_before', 'pain_after', 'attended', 'cost']);

        $activePlanList = PhysioTreatmentPlan::where('doctor_id', $doctorId)->active()
            ->with('patient:id,full_name,phone,photo')
            ->latest('start_date')
            ->limit(10)
            ->get()
            ->map(fn ($p) => $this->decoratePlan($p));

        return Inertia::render('Doctor/Physiotherapy/Dashboard', [
            'stats' => [
                'active_plans' => $activePlans,
                'sessions_this_month' => $sessionsThisMonth,
                'assessments_this_month' => $assessmentsThisMonth,
            ],
            'recentSessions' => $recentSessions,
            'activePlans' => $activePlanList,
        ]);
    }

    /** Searchable list of patients this doctor treats in physiotherapy. */
    public function patients(Request $request): Response
    {
        $doctorId = $this->doctorId($request);
        $search = trim((string) $request->input('search'));

        // Patient IDs touched by this doctor across any physio event table.
        $ids = collect()
            ->merge(PhysioTreatmentPlan::where('doctor_id', $doctorId)->pluck('patient_id'))
            ->merge(PhysioSession::where('doctor_id', $doctorId)->pluck('patient_id'))
            ->merge(PhysioAssessment::where('doctor_id', $doctorId)->pluck('patient_id'))
            ->unique()
            ->values();

        $patients = Patient::whereIn('id', $ids)
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('file_number', 'like', "%{$search}%")))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
                'file_number' => $p->file_number,
                'photo' => $p->photo,
                'active_plans' => PhysioTreatmentPlan::where('doctor_id', $doctorId)
                    ->where('patient_id', $p->id)->active()->count(),
            ]);

        return Inertia::render('Doctor/Physiotherapy/Patients/Index', [
            'patients' => $patients,
            'filters' => ['search' => $search],
        ]);
    }

    /** Full physiotherapy file for a patient: assessments, plan, session log. */
    public function patientShow(Request $request, Patient $patient): Response
    {
        $doctorId = $this->doctorId($request);

        $assessments = PhysioAssessment::where('patient_id', $patient->id)
            ->with(['romMeasurements', 'strengthTests', 'painPoints'])
            ->latest('assessment_date')
            ->limit(10)
            ->get();

        $plans = PhysioTreatmentPlan::where('patient_id', $patient->id)
            ->latest('start_date')
            ->get()
            ->map(fn ($p) => $this->decoratePlan($p));

        $sessions = PhysioSession::where('patient_id', $patient->id)
            ->latest('session_date')
            ->limit(30)
            ->get(['id', 'treatment_plan_id', 'session_number', 'session_date', 'attended', 'pain_before', 'pain_after', 'cost', 'invoice_id', 'soap', 'techniques']);

        // ROM trend (latest value per joint/motion over time) for the chart.
        $romHistory = \App\Models\PhysioRomMeasurement::where('patient_id', $patient->id)
            ->orderBy('recorded_at')
            ->get(['joint', 'motion', 'side', 'arom', 'prom', 'normal_ref', 'recorded_at']);

        // Home-exercise-program: the shared catalog + this patient's prescriptions
        // (with a 14-day adherence count for the progress chip).
        $catalog = \App\Models\Exercise::where('is_active', true)
            ->orderBy('region')->orderBy('name_en')
            ->get(['id', 'name_ar', 'name_en', 'region', 'default_sets', 'default_reps', 'default_hold_sec']);

        $prescriptions = \App\Models\PhysioExercisePrescription::where('patient_id', $patient->id)
            ->with('exercise:id,name_ar,name_en,region')
            ->latest('prescribed_at')
            ->get()
            ->map(fn ($rx) => array_merge(
                $rx->only(['id', 'exercise_id', 'sets', 'reps', 'hold_sec', 'frequency', 'resistance', 'status', 'prescribed_at', 'notes']),
                [
                    'exercise' => $rx->exercise,
                    'adherence_14d' => \App\Models\HepAdherenceLog::where('prescription_id', $rx->id)
                        ->where('done', true)
                        ->where('log_date', '>=', now()->subDays(14)->toDateString())
                        ->count(),
                ]
            ));

        // PROMs (ODI/NDI/LEFS) via the shared scale engine, newest first.
        $promKeys = array_keys(\App\Services\NeuroPsych\ScaleEngine::forModule('physiotherapy'));
        $scaleResults = \App\Models\ScaleResult::where('patient_id', $patient->id)
            ->whereIn('scale_key', $promKeys)
            ->orderBy('taken_at')
            ->get(['scale_key', 'score', 'severity', 'taken_at']);

        return Inertia::render('Doctor/Physiotherapy/Patients/Show', [
            'patient' => $patient->only(['id', 'full_name', 'phone', 'file_number', 'photo', 'date_of_birth', 'gender']),
            'assessments' => $assessments,
            'plans' => $plans,
            'sessions' => $sessions,
            'romHistory' => $romHistory,
            'romNormatives' => RomNormatives::NORMS,
            'exerciseCatalog' => $catalog,
            'prescriptions' => $prescriptions,
            'promCatalog' => $this->promCatalog(),
            'scaleResults' => $scaleResults,
            'packageCatalog' => \App\Models\PhysioPackage::where('is_active', true)->orderBy('total_sessions')->get(['id', 'name_ar', 'name_en', 'total_sessions', 'price']),
            'packages' => \App\Models\PhysioPackagePurchase::where('patient_id', $patient->id)
                ->with('package:id,name_ar,name_en')
                ->latest('purchased_at')->get()
                ->map(fn ($p) => array_merge(
                    $p->only(['id', 'total_sessions', 'sessions_used', 'amount', 'status', 'purchased_at', 'expires_at']),
                    ['sessions_remaining' => $p->sessions_remaining, 'is_usable' => $p->is_usable, 'package' => $p->package]
                )),
        ]);
    }

    /** PROM definitions for this module shaped for the questionnaire runner. */
    private function promCatalog(): array
    {
        $out = [];
        foreach (\App\Services\NeuroPsych\ScaleEngine::forModule('physiotherapy') as $key => $def) {
            $out[] = [
                'key' => $key,
                'name_en' => $def['name_en'],
                'name_ar' => $def['name_ar'],
                'mcid' => $def['mcid'] ?? null,
                'higher_is_better' => (bool) ($def['higher_is_better'] ?? false),
                'options' => $def['options'],
                'items' => $def['items'],
            ];
        }

        return $out;
    }

    /** Prescribe an exercise from the shared catalog (part of the HEP). */
    public function prescribeExercise(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validate([
            'exercise_id' => 'required|integer|exists:exercises,id',
            'treatment_plan_id' => 'nullable|integer|exists:physio_treatment_plans,id',
            'sets' => 'nullable|integer|min:1|max:20',
            'reps' => 'nullable|integer|min:1|max:100',
            'hold_sec' => 'nullable|integer|min:0|max:600',
            'frequency' => 'nullable|string|max:40',
            'resistance' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:500',
        ]);

        $rx = \App\Models\PhysioExercisePrescription::create(array_merge($data, [
            'patient_id' => $patient->id,
            'doctor_id' => $doctorId,
            'status' => 'active',
            'prescribed_at' => now()->toDateString(),
        ]));

        AuditLogger::log('created', $rx, ['patient_id' => $patient->id], 'Prescribed home exercise');

        return back()->with('success', $this->msg('Exercise prescribed.', 'تم وصف التمرين.'));
    }

    /** Sell a prepaid session package to a patient (creates the purchase + an invoice). */
    public function purchasePackage(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validate([
            'package_id' => 'required|integer|exists:physio_packages,id',
        ]);

        $package = \App\Models\PhysioPackage::findOrFail($data['package_id']);

        DB::transaction(function () use ($package, $patient, $doctorId, $request) {
            $invoice = \App\Models\Invoice::create([
                'invoice_number' => \App\Models\Invoice::generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'patient_id' => $patient->id,
                'subtotal' => $package->price,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => $package->price,
                'module' => 'physiotherapy',
                'created_by' => $request->user()->id,
            ]);
            $invoice->forceFill(['paid_amount' => 0, 'status' => 'unpaid'])->save();
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => 'Physiotherapy package — '.$package->name_en,
                'description_ar' => 'باقة علاج طبيعي — '.$package->name_ar,
                'quantity' => 1, 'unit_price' => $package->price, 'discount' => 0, 'total' => $package->price,
            ]);

            \App\Models\PhysioPackagePurchase::create([
                'patient_id' => $patient->id,
                'package_id' => $package->id,
                'invoice_id' => $invoice->id,
                'doctor_id' => $doctorId,
                'created_by' => $request->user()->id,
                'total_sessions' => $package->total_sessions,
                'sessions_used' => 0,
                'amount' => $package->price,
                'purchased_at' => now()->toDateString(),
                'expires_at' => $package->validity_days ? now()->addDays($package->validity_days)->toDateString() : null,
                'status' => 'active',
            ]);
        });

        return back()->with('success', $this->msg('Package sold.', 'تم بيع الباقة.'));
    }

    /** Record a patient-reported outcome measure (ODI/NDI/LEFS) via ScaleEngine. */
    public function storeScale(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'scale_key' => 'required|string',
            'answers' => 'required|array|min:1',
        ]);

        $def = \App\Services\NeuroPsych\ScaleEngine::definition($data['scale_key']);
        if (! $def || $def['module'] !== 'physiotherapy') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'scale_key' => $this->msg('This measure is not available here.', 'هذا المقياس غير متاح هنا.'),
            ]);
        }

        $result = \App\Models\ScaleResult::build($patient->id, $data['scale_key'], $data['answers'], 'doctor');
        $result->save();

        AuditLogger::log('created', $result, ['patient_id' => $patient->id], 'Recorded physiotherapy PROM');

        return back()->with('success', $this->msg('Outcome measure recorded.', 'تم تسجيل مقياس النتيجة.'));
    }

    /** Retire a prescription (soft stop — kept for history). */
    public function removeExercise(Request $request, \App\Models\PhysioExercisePrescription $prescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $prescription);

        $prescription->update(['status' => 'stopped']);
        AuditLogger::log('updated', $prescription, ['status' => 'stopped'], 'Stopped home exercise');

        return back()->with('success', $this->msg('Exercise stopped.', 'تم إيقاف التمرين.'));
    }

    /** Record an assessment + its ROM, MMT and pain-map rows in one transaction. */
    public function storeAssessment(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validate([
            'assessment_date' => 'required|date',
            'subjective' => 'nullable|string|max:4000',
            'objective' => 'nullable|string|max:4000',
            'icd10' => 'nullable|string|max:20',
            'icf' => 'nullable|array',
            'red_flags' => 'nullable|array',
            'posture' => 'nullable|string|max:2000',
            'gait' => 'nullable|string|max:2000',
            'balance' => 'nullable|string|max:2000',
            'special_tests' => 'nullable|array',
            'diagnosis' => 'nullable|string|max:2000',
            'plan' => 'nullable|string|max:4000',
            'visit_id' => 'nullable|integer',
            'rom' => 'nullable|array',
            'rom.*.joint' => 'required_with:rom|string|max:40',
            'rom.*.motion' => 'required_with:rom|string|max:40',
            'rom.*.side' => 'nullable|in:left,right,bilateral',
            'rom.*.arom' => 'nullable|numeric|min:0|max:360',
            'rom.*.prom' => 'nullable|numeric|min:0|max:360',
            'strength' => 'nullable|array',
            'strength.*.muscle_group' => 'required_with:strength|string|max:60',
            'strength.*.side' => 'nullable|in:left,right,bilateral',
            'strength.*.grade' => 'nullable|integer|min:0|max:5',
            'pain_points' => 'nullable|array',
            'pain_points.*.view' => 'required_with:pain_points|in:front,back',
            'pain_points.*.x' => 'required_with:pain_points|numeric|min:0|max:100',
            'pain_points.*.y' => 'required_with:pain_points|numeric|min:0|max:100',
            'pain_points.*.intensity' => 'nullable|integer|min:0|max:10',
            'pain_points.*.pain_type' => 'nullable|string|max:30',
            'pain_points.*.note' => 'nullable|string|max:255',
        ]);

        $assessment = DB::transaction(function () use ($data, $patient, $doctorId) {
            $assessment = PhysioAssessment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'visit_id' => $data['visit_id'] ?? null,
                'assessment_date' => $data['assessment_date'],
                'subjective' => $data['subjective'] ?? null,
                'objective' => $data['objective'] ?? null,
                'icd10' => $data['icd10'] ?? null,
                'icf' => $data['icf'] ?? null,
                'red_flags' => $data['red_flags'] ?? null,
                'posture' => $data['posture'] ?? null,
                'gait' => $data['gait'] ?? null,
                'balance' => $data['balance'] ?? null,
                'special_tests' => $data['special_tests'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'plan' => $data['plan'] ?? null,
                'completed_at' => now(),
            ]);

            foreach ($data['rom'] ?? [] as $rom) {
                $assessment->romMeasurements()->create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctorId,
                    'joint' => $rom['joint'],
                    'motion' => $rom['motion'],
                    'side' => $rom['side'] ?? 'right',
                    'arom' => $rom['arom'] ?? null,
                    'prom' => $rom['prom'] ?? null,
                    'normal_ref' => RomNormatives::for($rom['joint'], $rom['motion']),
                    'recorded_at' => $data['assessment_date'],
                ]);
            }

            foreach ($data['strength'] ?? [] as $mmt) {
                $assessment->strengthTests()->create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctorId,
                    'muscle_group' => $mmt['muscle_group'],
                    'side' => $mmt['side'] ?? 'right',
                    'grade' => $mmt['grade'] ?? null,
                    'recorded_at' => $data['assessment_date'],
                ]);
            }

            foreach ($data['pain_points'] ?? [] as $pp) {
                $assessment->painPoints()->create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctorId,
                    'view' => $pp['view'],
                    'x' => $pp['x'],
                    'y' => $pp['y'],
                    'intensity' => $pp['intensity'] ?? null,
                    'pain_type' => $pp['pain_type'] ?? null,
                    'note' => $pp['note'] ?? null,
                    'recorded_at' => $data['assessment_date'],
                ]);
            }

            return $assessment;
        });

        AuditLogger::log('created', $assessment, ['patient_id' => $patient->id], 'Recorded physiotherapy assessment');

        return back()->with('success', $this->msg('Assessment recorded.', 'تم تسجيل التقييم.'));
    }

    /** Open a plan of care. */
    public function storePlan(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validate([
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'problem_list' => 'nullable|string|max:4000',
            'goals' => 'nullable|array',
            'modalities' => 'nullable|array',
            'frequency' => 'nullable|string|max:40',
            'duration_weeks' => 'nullable|integer|min:1|max:104',
            'estimated_sessions' => 'nullable|integer|min:0|max:500',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string|max:4000',
        ]);

        $plan = PhysioTreatmentPlan::create(array_merge($data, [
            'patient_id' => $patient->id,
            'doctor_id' => $doctorId,
            'status' => PhysioTreatmentPlan::STATUS_IN_PROGRESS,
            'completed_sessions' => 0,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
        ]));

        AuditLogger::log('created', $plan, ['patient_id' => $patient->id], 'Opened physiotherapy plan');

        return back()->with('success', $this->msg('Treatment plan created.', 'تم إنشاء الخطة العلاجية.'));
    }

    /** Change a plan's status (on_hold / completed / cancelled / in_progress). */
    public function updatePlanStatus(Request $request, PhysioTreatmentPlan $plan): RedirectResponse
    {
        $this->authorizeDoctor($request, $plan);

        $data = $request->validate([
            'status' => 'required|in:planned,in_progress,on_hold,completed,cancelled',
        ]);

        $plan->update(['status' => $data['status']]);
        AuditLogger::log('updated', $plan, ['status' => $data['status']], 'Updated plan status');

        return back()->with('success', $this->msg('Plan updated.', 'تم تحديث الخطة.'));
    }

    /** Log a treatment session; optionally bill it and advance the plan counter. */
    public function storeSession(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validate([
            'treatment_plan_id' => 'nullable|integer|exists:physio_treatment_plans,id',
            'package_purchase_id' => 'nullable|integer|exists:physio_package_purchases,id',
            'visit_id' => 'nullable|integer',
            'session_date' => 'required|date',
            'soap' => 'nullable|string|max:4000',
            'modalities' => 'nullable|array',
            'techniques' => 'nullable|string|max:2000',
            'exercises_done' => 'nullable|string|max:2000',
            'attended' => 'boolean',
            'home_visit' => 'boolean',
            'pain_before' => 'nullable|integer|min:0|max:10',
            'pain_after' => 'nullable|integer|min:0|max:10',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'bill' => 'boolean',
        ]);

        $plan = null;
        if (! empty($data['treatment_plan_id'])) {
            $plan = PhysioTreatmentPlan::where('patient_id', $patient->id)->find($data['treatment_plan_id']);
        }
        // A session drawn against a prepaid package is covered (not billed).
        $package = null;
        if (! empty($data['package_purchase_id'])) {
            $package = \App\Models\PhysioPackagePurchase::where('patient_id', $patient->id)->find($data['package_purchase_id']);
        }
        $attended = $request->boolean('attended', true);
        $homeVisit = $request->boolean('home_visit', false);

        $session = DB::transaction(function () use ($data, $patient, $doctorId, $plan, $package, $attended, $homeVisit) {
            $nextNumber = $plan
                ? ((int) $plan->completed_sessions + 1)
                : (PhysioSession::where('patient_id', $patient->id)->max('session_number') + 1);

            $covered = $package && $package->is_usable;

            $session = PhysioSession::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'treatment_plan_id' => $plan?->id,
                'package_purchase_id' => $covered ? $package->id : null,
                'visit_id' => $data['visit_id'] ?? null,
                'session_number' => $nextNumber,
                'session_date' => $data['session_date'],
                'soap' => $data['soap'] ?? null,
                'modalities' => $data['modalities'] ?? null,
                'techniques' => $data['techniques'] ?? null,
                'exercises_done' => $data['exercises_done'] ?? null,
                'attended' => $attended,
                'home_visit' => $homeVisit,
                'pain_before' => $data['pain_before'] ?? null,
                'pain_after' => $data['pain_after'] ?? null,
                'cost' => $covered ? 0 : ($data['cost'] ?? null),
                'completed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Advance the plan's completed-session counter for attended sessions.
            if ($plan && $session->attended) {
                $plan->increment('completed_sessions');
                if ($plan->estimated_sessions > 0 && $plan->completed_sessions >= $plan->estimated_sessions) {
                    $plan->update(['status' => PhysioTreatmentPlan::STATUS_COMPLETED]);
                }
            }

            // Draw the session down against the prepaid package.
            if ($covered) {
                $package->drawDown();
            }

            return $session;
        });

        // Package-covered sessions are prepaid → never billed individually.
        if (! $session->package_purchase_id && $request->boolean('bill', true)) {
            $this->billing->billSession($session);
        }

        AuditLogger::log('created', $session, ['patient_id' => $patient->id], 'Logged physiotherapy session');

        return back()->with('success', $this->msg('Session recorded.', 'تم تسجيل الجلسة.'));
    }

    /**
     * Schedule a recurring series of physiotherapy_session bookings (weekly by
     * default) for a patient — e.g. "3x/week for 4 weeks". Each occurrence is a
     * real booking created through the tested BookingWorkflowService, so it shows
     * up in schedules + the secretary front desk and bills like any session.
     */
    public function scheduleSeries(Request $request, Patient $patient): RedirectResponse
    {
        $doctor = $this->doctor($request);

        $data = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'occurrences' => 'required|integer|min:1|max:60',
            'interval_days' => 'nullable|integer|min:1|max:30',
            'treatment_plan_id' => 'nullable|integer|exists:physio_treatment_plans,id',
        ]);

        $planId = $data['treatment_plan_id'] ?? null;
        $interval = (int) ($data['interval_days'] ?? 7);
        $fee = (float) \App\Services\ModuleManager::getSetting('physiotherapy', 'session_fee', 0);
        $durationMin = (int) \App\Services\ModuleManager::getSetting('physiotherapy', 'session_duration', 45);
        $userId = $request->user()->id;
        $workflow = app(\App\Services\BookingWorkflowService::class);

        $start = \Illuminate\Support\Carbon::parse($data['start_date']);
        $created = 0;
        for ($i = 0; $i < (int) $data['occurrences']; $i++) {
            $date = $start->copy()->addDays($interval * $i)->toDateString();
            $startTime = $data['start_time'];
            $endTime = \Illuminate\Support\Carbon::parse($date.' '.$startTime)->addMinutes($durationMin)->format('H:i');

            $workflow->createFromSecretary([
                'patient_id' => $patient->id,
                'full_name' => $patient->full_name,
                'phone' => $patient->phone,
                'booking_type' => 'physiotherapy_session',
                'source' => 'doctor',
                'notes' => $planId ? "Plan #{$planId} — session ".($i + 1) : null,
                'services' => [[
                    'doctor_id' => $doctor->id,
                    'unit_price' => $fee,
                    'sessions_count' => 1,
                    'appointments' => [[
                        'date' => $date, 'start_time' => $startTime, 'end_time' => $endTime, 'doctor_id' => $doctor->id,
                    ]],
                ]],
            ], $userId);
            $created++;
        }

        AuditLogger::log('created', $patient, ['occurrences' => $created], 'Scheduled physiotherapy session series');

        return back()->with('success', $this->msg("Scheduled {$created} sessions.", "تم جدولة {$created} جلسة."));
    }

    /** Active/all plans across this doctor's caseload. */
    public function treatmentPlans(Request $request): Response
    {
        $doctorId = $this->doctorId($request);
        $status = $request->input('status', 'active');

        $plans = PhysioTreatmentPlan::where('doctor_id', $doctorId)
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status !== 'active' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->with('patient:id,full_name,phone,photo')
            ->latest('start_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => $this->decoratePlan($p));

        return Inertia::render('Doctor/Physiotherapy/TreatmentPlans', [
            'plans' => $plans,
            'filters' => ['status' => $status],
        ]);
    }

    /** Attach the computed progress fields a plan card needs. */
    private function decoratePlan(PhysioTreatmentPlan $plan): array
    {
        return array_merge(
            $plan->toArray(),
            [
                'progress_percentage' => $plan->progress_percentage,
                'sessions_remaining' => $plan->sessions_remaining,
            ]
        );
    }
}

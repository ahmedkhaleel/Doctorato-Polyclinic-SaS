<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\VisitWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    public function __construct(
        protected VisitWorkflowService $workflowService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Visit::with(['patient', 'doctor', 'service', 'booking']);

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('doctor', function ($dq) use ($search) {
                    $dq->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($visitType = $request->input('visit_type')) {
            $query->where('visit_type', $visitType);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('visit_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('visit_date', '<=', $dateTo);
        }

        $visits = $query->latest('visit_date')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Visits/Index', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status', 'visit_type', 'date_from', 'date_to', 'module']),
        ]);
    }

    public function todayQueue(Request $request): Response
    {
        $visits = Visit::with(['patient', 'service', 'dentalTreatments:id,visit_id,treatment_type,tooth_number'])
            ->today()
            ->orderBy('created_at')
            ->get()
            ->groupBy('doctor_id');

        $doctors = Doctor::active()->get();

        return Inertia::render('Admin/Visits/TodayQueue', [
            'visitsByDoctor' => $visits,
            'doctors' => $doctors,
        ]);
    }

    public function show(Visit $visit): Response
    {
        $visit->load([
            'patient',
            'doctor',
            'service',
            'receptionist',
            'booking',
            'bookingAppointment',
            'prescriptions.items',
            'prescriptions.doctor',
            'invoice.payments.paymentMethod',
            'invoice.items',
            'photos',
            'supplyTransactions.supply',
            'dentalTreatments',
        ]);

        $extra = [];

        // Load dental data for dental visits
        if ($visit->module === 'dental' && $visit->patient_id) {
            $extra['dentalChart'] = \App\Models\DentalChart::where('patient_id', $visit->patient_id)
                ->orderBy('tooth_number')->get()->keyBy('tooth_number');
            $extra['dentalXrays'] = \App\Models\DentalXray::where('patient_id', $visit->patient_id)
                ->latest('taken_date')->limit(10)->get();
            $extra['dentalConditions'] = \App\Models\DentalChart::CONDITIONS;
            $extra['allTeeth'] = \App\Models\DentalChart::ALL_TEETH;
            $extra['treatmentTypes'] = \App\Models\DentalTreatment::TYPES;
            $extra['dentalPlans'] = \App\Models\DentalTreatmentPlan::where('patient_id', $visit->patient_id)
                ->whereIn('status', ['draft', 'approved', 'in_progress'])
                ->with('doctor:id,name_ar,name_en')
                ->withCount('treatments')
                ->get();
        }

        // ── Derma: active treatment course + session log ─────
        if ($visit->module === 'derma' && $visit->patient_id) {
            $activePlan = \App\Models\DermaTreatmentPlan::where('patient_id', $visit->patient_id)
                ->active()
                ->latest('start_date')
                ->first();
            if ($activePlan) {
                $extra['dermaActivePlan'] = array_merge(
                    $activePlan->only(['id', 'title_ar', 'title_en', 'session_type', 'estimated_sessions', 'completed_sessions', 'interval_days', 'status', 'start_date']),
                    ['progress_percentage' => $activePlan->progress_percentage, 'sessions_remaining' => $activePlan->sessions_remaining]
                );
            }
            $extra['dermaSessions'] = \App\Models\DermaSession::where('patient_id', $visit->patient_id)
                ->orderByRaw('COALESCE(completed_at, created_at) DESC')
                ->limit(8)
                ->get(['id', 'visit_id', 'session_type', 'area_treated', 'product_used', 'session_number', 'total_sessions', 'cost', 'completed_at', 'next_session_date']);
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
                    $pregnancy->only(['id', 'lmp', 'edd', 'gravida', 'para', 'blood_group', 'rh_factor', 'is_high_risk', 'risk_factors', 'conception_method', 'status']),
                    ['gestational_weeks' => $days !== null ? intdiv($days, 7) : null, 'gestational_days' => $days !== null ? $days % 7 : null]
                );
                $extra['obgynLabTests'] = $pregnancy->labTests()
                    ->latest('result_date')
                    ->limit(6)
                    ->get(['id', 'test_type', 'value', 'unit', 'reference_range', 'result_date', 'is_abnormal']);
            }
        }

        // ── Psychiatry / Neurology: encounter + scales + meds + (gated) risk ──
        if (in_array($visit->module, ['psychiatry', 'neurology'], true) && $visit->patient_id) {
            $module = $visit->module;
            $encounter = \App\Models\NeuropsychEncounter::where('patient_id', $visit->patient_id)
                ->where('module', $module)->where('visit_id', $visit->id)->first()
                ?? \App\Models\NeuropsychEncounter::where('patient_id', $visit->patient_id)
                    ->where('module', $module)->latest('encounter_date')->first();
            if ($encounter) {
                $extra['neuroEncounter'] = $encounter->only(['id', 'note_format', 'subjective', 'objective', 'assessment', 'plan', 'mse', 'encounter_date', 'visit_id']);
            }
            $extra['neuroScales'] = \App\Models\ScaleResult::where('patient_id', $visit->patient_id)
                ->latest('taken_at')->limit(12)->get(['id', 'scale_key', 'score', 'severity', 'flag', 'taken_at']);
            $extra['neuroMeds'] = \App\Models\MedicationPlan::where('patient_id', $visit->patient_id)
                ->where('module', $module)->whereNull('stopped_at')->latest('started_at')
                ->get(['id', 'drug', 'drug_class', 'dose', 'frequency', 'route', 'is_controlled', 'started_at']);

            $extra['neuroSeizures'] = \App\Models\SeizureDiaryEntry::where('patient_id', $visit->patient_id)
                ->where('occurred_at', '>=', now()->subDays(90))->orderBy('occurred_at')
                ->get(['id', 'occurred_at', 'seizure_type', 'duration_seconds']);
            $extra['neuroHeadaches'] = \App\Models\HeadacheDiaryEntry::where('patient_id', $visit->patient_id)
                ->whereDate('date', '>=', now()->subDays(90)->toDateString())->orderBy('date')
                ->get(['id', 'date', 'intensity', 'ichd3_type', 'aura']);

            $canSensitive = (bool) (auth()->user()?->role?->hasPermission("{$module}.view_sensitive") ?? false);
            $extra['neuroCanViewSensitive'] = $canSensitive;
            if ($canSensitive) {
                $risk = \App\Models\RiskAssessment::where('patient_id', $visit->patient_id)
                    ->where('is_active', true)->latest('assessed_at')->first();
                if ($risk) {
                    $extra['neuroRisk'] = $risk->only(['id', 'type', 'tool', 'risk_level', 'assessed_at']);
                    \App\Models\MedicalDataAccessLog::record($visit->patient_id, 'view', 'neuropsych_risk', ['risk_level', 'type'], 'Admin visit page risk summary');
                }
            }
        }

        // Doctors + services for the reschedule/reassign modal — kept
        // lean (id + display name) so we don't bloat the visit show
        // payload for the common read-only case.
        $doctors = Doctor::active()
            ->where('module', $visit->module)
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_en')
            ->get();
        $services = \App\Models\Service::active()
            ->where('module', $visit->module)
            ->select('id', 'name_ar', 'name_en', 'price')
            ->orderBy('name_en')
            ->get();

        return Inertia::render('Admin/Visits/Show', array_merge([
            'visit' => $visit,
            'doctors' => $doctors,
            'services' => $services,
        ], $extra));
    }

    public function start(Visit $visit): RedirectResponse
    {
        $this->workflowService->start($visit);

        AuditLogger::log('started', $visit);

        return redirect()->back()->with('success', 'Visit started.');
    }

    public function complete(Visit $visit): RedirectResponse
    {
        $results = $this->workflowService->complete($visit);

        AuditLogger::log('completed', $visit);

        $message = 'Visit completed.';
        if ($results['invoice']) {
            $message .= ' Invoice #'.$results['invoice']->invoice_number.' generated.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function updateDiagnosis(Request $request, Visit $visit): RedirectResponse
    {
        $data = $request->validate([
            'diagnosis' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
        ]);

        $visit->update($data);

        AuditLogger::log('updated_diagnosis', $visit);

        return redirect()->back()->with('success', 'Diagnosis updated successfully.');
    }

    public function uploadPhoto(Request $request, Visit $visit): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'caption' => 'nullable|string|max:255',
            'photo_type' => 'nullable|string|in:before,after,during',
        ]);

        $path = $request->file('photo')->store('visit-photos/'.$visit->id, 'local');

        $visit->photos()->create([
            'photo_path' => $path,
            'caption' => $request->input('caption'),
            'photo_type' => $request->input('photo_type', 'during'),
            'taken_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Photo uploaded successfully.');
    }

    /**
     * Edit the scheduling details of a visit — visit date, scheduled
     * time, doctor, and service. Used when a doctor calls in sick,
     * the patient asks to switch providers, or the front desk needs
     * to shift the time slot.
     *
     * Completed / cancelled visits are protected: their facts are
     * historical and shouldn't move retroactively. Use a new visit
     * for those cases.
     */
    public function updateDetails(Request $request, Visit $visit): RedirectResponse
    {
        if (in_array($visit->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Completed or cancelled visits cannot be edited.');
        }

        $data = $request->validate([
            'visit_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
        ]);

        $changes = [];
        foreach ($data as $key => $newValue) {
            $oldValue = $visit->{$key};
            // normalise date for comparison
            if ($key === 'visit_date' && $oldValue instanceof \DateTimeInterface) {
                $oldValue = $oldValue->format('Y-m-d');
            }
            if ((string) $oldValue !== (string) $newValue) {
                $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        // Enrich FK changes with display names for the patient email
        $this->resolveChangeNames($changes);

        $visit->update($data);

        AuditLogger::log('visit_details_updated', $visit, $changes);

        // Notify the patient if anything actually moved
        if ($changes) {
            $this->maybeEmailRescheduled($visit->fresh(['patient', 'doctor', 'service']), $changes);
        }

        return redirect()->back()->with('success', 'Visit updated successfully.');
    }

    /**
     * Bring a CANCELLED visit back to life with a (possibly new) date,
     * time, doctor, and service. Common workflow: patient initially
     * cancelled then changed their mind; or staff cancelled by mistake.
     *
     * Goes to `waiting` so the patient still needs to be seen — never
     * resurrects directly into `completed`. Always notifies the patient
     * by email so they know the visit is back on the calendar.
     */
    public function restore(Request $request, Visit $visit): RedirectResponse
    {
        if ($visit->status !== 'cancelled') {
            return back()->with('error', 'Only cancelled visits can be restored.');
        }

        $data = $request->validate([
            'visit_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
        ]);

        // Build a changes array for the patient email — status itself
        // is the headline, then any scheduling shifts.
        $changes = [
            'status' => ['from' => 'cancelled', 'to' => 'waiting'],
        ];
        foreach ($data as $key => $newValue) {
            $oldValue = $visit->{$key};
            if ($key === 'visit_date' && $oldValue instanceof \DateTimeInterface) {
                $oldValue = $oldValue->format('Y-m-d');
            }
            if ((string) $oldValue !== (string) $newValue) {
                $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        $this->resolveChangeNames($changes);

        $visit->update(array_merge($data, ['status' => 'waiting']));

        AuditLogger::log('visit_restored', $visit, $changes);

        // Patient deserves to know the visit is back on the calendar.
        $this->maybeEmailRescheduled($visit->fresh(['patient', 'doctor', 'service']), $changes);

        return redirect()->back()->with('success', 'Visit restored and rescheduled.');
    }

    /**
     * Replace raw FK ids in $changes with from_name / to_name for use
     * in the patient email body. Edits the array in place.
     */
    protected function resolveChangeNames(array &$changes): void
    {
        if (isset($changes['doctor_id'])) {
            $fromId = $changes['doctor_id']['from'] ?? null;
            $toId = $changes['doctor_id']['to'] ?? null;
            $doctors = Doctor::whereIn('id', array_filter([$fromId, $toId]))
                ->pluck('name_ar', 'id');
            $changes['doctor_id']['from_name'] = $fromId ? ($doctors[$fromId] ?? '—') : '—';
            $changes['doctor_id']['to_name'] = $toId ? ($doctors[$toId] ?? '—') : '—';
        }
        if (isset($changes['service_id'])) {
            $fromId = $changes['service_id']['from'] ?? null;
            $toId = $changes['service_id']['to'] ?? null;
            $services = \App\Models\Service::whereIn('id', array_filter([$fromId, $toId]))
                ->pluck('name_ar', 'id');
            $changes['service_id']['from_name'] = $fromId ? ($services[$fromId] ?? '—') : '—';
            $changes['service_id']['to_name'] = $toId ? ($services[$toId] ?? '—') : '—';
        }
    }

    /**
     * Best-effort email send — failures log but never break the update.
     * Honors the patient's `notify_email_bookings` opt-in.
     */
    protected function maybeEmailRescheduled(Visit $visit, array $changes): void
    {
        $patient = $visit->patient;
        if (! $patient || ! $patient->email) {
            return;
        }
        if (! $patient->wantsNotification('bookings', 'email')) {
            return;
        }

        try {
            $newDoctorName = $visit->doctor?->name_ar ?? $visit->doctor?->name_en;
            $newServiceName = $visit->service?->name_ar ?? $visit->service?->name_en;

            \Illuminate\Support\Facades\Notification::route('mail', $patient->email)
                ->notify(new \App\Notifications\VisitRescheduledEmail($visit, $changes, $newDoctorName, $newServiceName));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[visit.rescheduled.email] failed', [
                'visit_id' => $visit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function cancel(Visit $visit): RedirectResponse
    {
        $this->workflowService->cancel($visit);

        AuditLogger::log('cancelled', $visit);

        return redirect()->back()->with('success', 'Visit cancelled.');
    }

    /**
     * Soft-delete a visit (the Visits list exposes a delete action gated by the
     * visits.delete permission; this is its backend). The Visit model uses
     * SoftDeletes, so the row is retained with deleted_at for audit/restore.
     */
    public function destroy(Visit $visit): RedirectResponse
    {
        AuditLogger::log('deleted', $visit);

        $visit->delete();

        return redirect()->route('admin.visits.index')->with('success', __('Visit deleted.'));
    }
}

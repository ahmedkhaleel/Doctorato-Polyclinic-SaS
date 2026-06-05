<?php

namespace App\Http\Controllers\Doctor;

use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\NeuroPsych\RiskAssessmentService;
use App\Services\NeuroPsychBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP1 — doctor-side clinical encounters for the psychiatry & neurology modules.
 * One controller serves both; the active module comes from the route default
 * `npModule` (psychiatry | neurology), set per route group.
 */
class NeuropsychEncounterController extends BaseDoctorController
{
    public function __construct(
        private NeuroPsychBillingService $billing,
        private RiskAssessmentService $risk,
    ) {}

    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function index(Request $request): Response
    {
        $module = $this->module($request);
        $doctorId = $this->doctorId($request);

        $encounters = NeuropsychEncounter::where('module', $module)
            ->where('doctor_id', $doctorId)
            ->with(['patient:id,full_name,phone,photo', 'diagnoses'])
            ->latest('encounter_date')
            ->paginate(20)
            ->withQueryString();

        // Active patient-safety signal for the patients on this page (banner + chips).
        // Only computed for users allowed to see sensitive risk data.
        $activeRisks = [];
        if ($request->user()?->role?->hasPermission("{$module}.view_sensitive")) {
            foreach (array_unique($encounters->pluck('patient_id')->all()) as $pid) {
                if ($risk = $this->risk->activeRiskFor((int) $pid)) {
                    $activeRisks[$pid] = $risk;
                }
            }
        }

        // Consultation visits for this doctor+module in the last 60 days that
        // aren't yet tied to an encounter — offered in the form so billing can
        // reuse the booking's invoice (avoids a duplicate charge).
        $linkedVisitIds = NeuropsychEncounter::where('module', $module)->whereNotNull('visit_id')->pluck('visit_id');
        $linkableVisits = \App\Models\Visit::where('doctor_id', $doctorId)
            ->where('module', $module)
            ->whereNotIn('id', $linkedVisitIds)
            ->whereDate('visit_date', '>=', now()->subDays(60)->toDateString())
            ->with('patient:id,full_name')
            ->latest('visit_date')
            ->limit(200)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'patient_id' => $v->patient_id,
                'label' => trim(($v->patient?->full_name ?? '#'.$v->patient_id).' — '.optional($v->visit_date)->format('Y-m-d')),
            ]);

        return Inertia::render('Doctor/Neuropsych/Encounters', [
            'module' => $module,
            'encounters' => $encounters,
            'patients' => Patient::active()->orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone']),
            'noteFormats' => NeuropsychEncounter::NOTE_FORMATS,
            'linkableVisits' => $linkableVisits,
            'activeRisks' => $activeRisks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $module = $this->module($request);
        $data = $this->validateData($request);

        $encounter = NeuropsychEncounter::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $this->doctorId($request),
            'module' => $module,
            'visit_id' => $data['visit_id'] ?? null,
            'encounter_date' => $data['encounter_date'],
            'note_format' => $data['note_format'],
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'mse' => $data['mse'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'completed_at' => $data['completed_at'] ?? null,
        ]);

        $this->syncDiagnoses($encounter, $data['diagnoses'] ?? []);

        if ($encounter->completed_at) {
            $this->billing->billEncounter($encounter->fresh());
        }

        AuditLogger::log('created', $encounter);

        return back()->with('success', $this->msg('Encounter saved', 'تم حفظ اللقاء'));
    }

    public function update(Request $request, NeuropsychEncounter $encounter): RedirectResponse
    {
        $this->authorizeDoctor($request, $encounter);
        $data = $this->validateData($request);

        $encounter->update([
            'visit_id' => $data['visit_id'] ?? null,
            'encounter_date' => $data['encounter_date'],
            'note_format' => $data['note_format'],
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'mse' => $data['mse'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'completed_at' => $data['completed_at'] ?? null,
        ]);

        $this->syncDiagnoses($encounter, $data['diagnoses'] ?? []);

        $fresh = $encounter->fresh();
        if ($fresh->completed_at === null) {
            $this->billing->reverse($fresh);          // un-completed → void any line
        } else {
            $this->billing->billEncounter($fresh);     // idempotent — never twice
        }

        return back()->with('success', $this->msg('Encounter updated', 'تم تحديث اللقاء'));
    }

    public function destroy(Request $request, NeuropsychEncounter $encounter): RedirectResponse
    {
        $this->authorizeDoctor($request, $encounter);
        $this->billing->reverse($encounter);
        $encounter->delete();

        return back()->with('success', $this->msg('Encounter deleted', 'تم حذف اللقاء'));
    }

    /** Replace the encounter's diagnoses with the submitted set. */
    private function syncDiagnoses(NeuropsychEncounter $encounter, array $diagnoses): void
    {
        $encounter->diagnoses()->delete();
        foreach ($diagnoses as $dx) {
            if (empty($dx['label'])) {
                continue;
            }
            $encounter->diagnoses()->create([
                'code_system' => in_array($dx['code_system'] ?? '', ['icd11', 'dsm5'], true) ? $dx['code_system'] : 'icd11',
                'code' => $dx['code'] ?? null,
                'label' => $dx['label'],
                'is_primary' => (bool) ($dx['is_primary'] ?? false),
            ]);
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'encounter_date' => 'required|date',
            'note_format' => 'required|in:soap,dap,birp',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'mse' => 'nullable|array',
            'cost' => 'nullable|numeric|min:0',
            'completed_at' => 'nullable|date',
            'diagnoses' => 'nullable|array',
            'diagnoses.*.code_system' => 'nullable|in:icd11,dsm5',
            'diagnoses.*.code' => 'nullable|string|max:32',
            'diagnoses.*.label' => 'nullable|string|max:255',
            'diagnoses.*.is_primary' => 'nullable|boolean',
        ]);
    }
}

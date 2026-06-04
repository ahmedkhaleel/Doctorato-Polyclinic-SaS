<?php

namespace App\Http\Controllers\Doctor;

use App\Models\MedicationMonitoring;
use App\Models\MedicationPlan;
use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\NeuroPsych\MedicationPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP4 — medication plans + safety monitoring + controlled-substance register
 * (record/track only). Auto-scheduled monitoring via MedicationPlanService.
 */
class NeuropsychMedicationController extends BaseDoctorController
{
    public function __construct(private MedicationPlanService $meds) {}

    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function index(Request $request): Response
    {
        $module = $this->module($request);
        $doctorId = $this->doctorId($request);

        $plans = MedicationPlan::where('module', $module)
            ->where('doctor_id', $doctorId)
            ->with(['patient:id,full_name', 'monitoring'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $dueMonitoring = MedicationMonitoring::where('status', 'due')
            ->whereHas('plan', fn ($q) => $q->where('module', $module)->where('doctor_id', $doctorId))
            ->with('plan.patient:id,full_name')
            ->orderBy('due_at')
            ->limit(50)
            ->get();

        return Inertia::render('Doctor/Neuropsych/Medications', [
            'module' => $module,
            'plans' => $plans,
            'dueMonitoring' => $dueMonitoring,
            'patients' => Patient::active()->orderBy('full_name')->limit(500)->get(['id', 'full_name']),
            'drugClasses' => ['clozapine', 'lithium', 'antipsychotic', 'ssri', 'aed', 'stimulant', 'benzodiazepine', 'other'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $module = $this->module($request);
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'drug' => 'required|string|max:255',
            'drug_class' => 'nullable|string|max:40',
            'dose' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:20',
            'started_at' => 'nullable|date',
            'is_controlled' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $plan = $this->meds->createPlan(array_merge($data, [
            'module' => $module,
            'doctor_id' => $this->doctorId($request),
            'started_at' => $data['started_at'] ?? now()->toDateString(),
            'is_controlled' => (bool) ($data['is_controlled'] ?? false),
        ]));

        AuditLogger::log('created', $plan);

        return back()->with('success', $this->msg('Medication plan saved', 'تم حفظ خطة الدواء'));
    }

    public function stop(Request $request, MedicationPlan $medicationPlan): RedirectResponse
    {
        $this->authorizeDoctor($request, $medicationPlan);
        $medicationPlan->update(['stopped_at' => now()->toDateString()]);

        return back()->with('success', $this->msg('Medication stopped', 'تم إيقاف الدواء'));
    }

    public function recordMonitoring(Request $request, MedicationMonitoring $monitoring): RedirectResponse
    {
        $data = $request->validate([
            'result' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $this->meds->recordResult($monitoring, $data['result'], $data['notes'] ?? null);

        return back()->with('success', $this->msg('Result recorded', 'تم تسجيل النتيجة'));
    }

    public function destroy(Request $request, MedicationPlan $medicationPlan): RedirectResponse
    {
        $this->authorizeDoctor($request, $medicationPlan);
        $medicationPlan->delete();

        return back()->with('success', $this->msg('Deleted', 'تم الحذف'));
    }
}

<?php

namespace App\Http\Controllers\Doctor;

use App\Models\MedicationMonitoring;
use App\Models\MedicationPlan;
use App\Models\NeuropsychEncounter;
use App\Models\RiskAssessment;
use App\Models\ScaleResult;
use App\Models\TreatmentCourse;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared doctor cockpit for psychiatry & neurology (mirrors how NeuroPsychPanel
 * serves both). Aggregates the doctor's caseload: today's queue, measurement-
 * based-care (flagged scales + severity mix), medication-monitoring due, active
 * treatment courses, and — gated by {module}.view_sensitive + audited — the
 * elevated-risk register. Patient-scoped to this doctor, N+1-safe.
 */
class NeuropsychDashboardController extends BaseDoctorController
{
    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function index(Request $request): Response
    {
        $module = $this->module($request);
        $doctorId = $this->doctorId($request);
        $canSensitive = (bool) ($request->user()?->role?->hasPermission("{$module}.view_sensitive") ?? false);

        // Patients this doctor sees in this module (scope MBC to them).
        $patientIds = NeuropsychEncounter::where('module', $module)->where('doctor_id', $doctorId)
            ->distinct()->pluck('patient_id');
        $planIds = MedicationPlan::where('module', $module)->where('doctor_id', $doctorId)->pluck('id');

        return Inertia::render('Doctor/Neuropsych/Dashboard', [
            'module' => $module,
            'canSeeSensitive' => $canSensitive,
            'stats' => $this->stats($module, $doctorId, $patientIds, $planIds),
            'todayQueue' => $this->todayQueue($module, $doctorId),
            'flaggedScales' => $this->flaggedScales($patientIds),
            'severityMix' => $this->severityMix($patientIds),
            'monitoringDue' => $this->monitoringDue($planIds),
            'courses' => $this->courses($module, $doctorId),
            'recentEncounters' => $this->recentEncounters($module, $doctorId),
            'riskRegister' => $canSensitive ? $this->riskRegister($module, $doctorId, $patientIds) : [],
        ]);
    }

    private function stats(string $module, int $doctorId, $patientIds, $planIds): array
    {
        return [
            'active_cases' => $patientIds->count(),
            'encounters_this_month' => NeuropsychEncounter::where('module', $module)->where('doctor_id', $doctorId)
                ->whereMonth('encounter_date', now()->month)->whereYear('encounter_date', now()->year)->count(),
            'active_medications' => MedicationPlan::where('module', $module)->where('doctor_id', $doctorId)->whereNull('stopped_at')->count(),
            'scales_this_month' => ScaleResult::whereIn('patient_id', $patientIds)
                ->whereMonth('taken_at', now()->month)->whereYear('taken_at', now()->year)->count(),
            'monitoring_due' => MedicationMonitoring::whereIn('medication_plan_id', $planIds)
                ->where('status', 'due')->whereDate('due_at', '<=', now())->count(),
            'active_high_risk' => RiskAssessment::whereIn('patient_id', $patientIds)
                ->where('is_active', true)->whereIn('risk_level', ['moderate', 'high'])->count(),
        ];
    }

    private function todayQueue(string $module, int $doctorId): array
    {
        return Visit::where('doctor_id', $doctorId)->where('module', $module)
            ->whereDate('visit_date', today())
            ->with('patient:id,full_name,file_number,photo')
            ->orderBy('scheduled_time')
            ->get(['id', 'patient_id', 'status', 'scheduled_time'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'patient' => $v->patient ? $v->patient->only(['id', 'full_name', 'file_number', 'photo']) : null,
                'status' => $v->status,
                'time' => $v->scheduled_time,
            ])->all();
    }

    /** Recent scales that raised a clinical flag (e.g. PHQ-9 suicidality). */
    private function flaggedScales($patientIds): array
    {
        if ($patientIds->isEmpty()) {
            return [];
        }

        return ScaleResult::whereIn('patient_id', $patientIds)->where('flag', true)
            ->with('patient:id,full_name')
            ->latest('taken_at')->limit(8)->get(['id', 'patient_id', 'scale_key', 'score', 'severity', 'taken_at'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'patient' => $s->patient?->full_name,
                'scale_key' => $s->scale_key,
                'score' => $s->score,
                'severity' => $s->severity,
                'taken_at' => optional($s->taken_at)->toDateString(),
            ])->all();
    }

    /** Severity distribution of each patient's latest scale (MBC snapshot). */
    private function severityMix($patientIds): array
    {
        if ($patientIds->isEmpty()) {
            return [];
        }

        $latest = ScaleResult::whereIn('patient_id', $patientIds)
            ->orderByDesc('taken_at')
            ->get(['patient_id', 'severity', 'taken_at'])
            ->groupBy('patient_id')
            ->map(fn ($rows) => $rows->first()->severity);

        return collect($latest)->filter()->countBy()
            ->map(fn ($c, $sev) => ['severity' => $sev, 'count' => $c])
            ->values()->all();
    }

    private function monitoringDue($planIds): array
    {
        if ($planIds->isEmpty()) {
            return [];
        }

        return MedicationMonitoring::whereIn('medication_plan_id', $planIds)
            ->where('status', 'due')->whereDate('due_at', '<=', now())
            ->with('patient:id,full_name')
            ->orderBy('due_at')->limit(10)->get(['id', 'patient_id', 'type', 'due_at'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'patient' => $m->patient?->full_name,
                'type' => $m->type,
                'due_at' => optional($m->due_at)->toDateString(),
            ])->all();
    }

    private function courses(string $module, int $doctorId): array
    {
        return TreatmentCourse::where('module', $module)->where('doctor_id', $doctorId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('patient:id,full_name')
            ->withCount('sessions')
            ->latest('id')->limit(8)->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'patient' => $c->patient?->full_name,
                'type' => $c->type,
                'planned_sessions' => (int) $c->planned_sessions,
                'done_sessions' => (int) $c->sessions_count,
                'status' => $c->status,
            ])->all();
    }

    private function recentEncounters(string $module, int $doctorId): array
    {
        return NeuropsychEncounter::where('module', $module)->where('doctor_id', $doctorId)
            ->with('patient:id,full_name,photo')
            ->latest('encounter_date')->limit(8)->get(['id', 'patient_id', 'encounter_date', 'note_format'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'patient' => $e->patient ? $e->patient->only(['id', 'full_name', 'photo']) : null,
                'date' => optional($e->encounter_date)->toDateString(),
            ])->all();
    }

    /** SENSITIVE: elevated active risk assessments. Caller gates on view_sensitive. */
    private function riskRegister(string $module, int $doctorId, $patientIds): array
    {
        $rows = RiskAssessment::whereIn('patient_id', $patientIds)
            ->where('is_active', true)->whereIn('risk_level', ['moderate', 'high'])
            ->with('patient:id,full_name')
            ->latest('assessed_at')->limit(10)->get(['id', 'patient_id', 'type', 'risk_level', 'assessed_at']);

        if ($rows->isNotEmpty()) {
            \App\Models\MedicalDataAccessLog::record(
                $rows->first()->patient_id, 'view', 'neuropsych_risk', ['risk_level', 'type'], 'Neuropsych dashboard risk register'
            );
        }

        return $rows->map(fn ($r) => [
            'patient' => $r->patient?->full_name,
            'type' => $r->type,
            'risk_level' => $r->risk_level,
            'assessed_at' => optional($r->assessed_at)->toDateString(),
        ])->all();
    }
}

<?php

namespace App\Http\Controllers\Doctor;

use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Models\RiskAssessment;
use App\Services\AuditLogger;
use App\Services\NeuroPsych\RiskAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * NP3 — suicide-risk assessment (C-SSRS). SENSITIVE: routes are gated by
 * permission:{module}.view_sensitive (heightened RBAC) and every read/write is
 * audited. A moderate/high assessment requires a safety plan (enforced in the
 * service).
 */
class NeuropsychRiskController extends BaseDoctorController
{
    public function __construct(private RiskAssessmentService $risk) {}

    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'answers' => 'required|array',
            'answers.q1' => 'nullable|boolean',
            'answers.q2' => 'nullable|boolean',
            'answers.q3' => 'nullable|boolean',
            'answers.q4' => 'nullable|boolean',
            'answers.q5' => 'nullable|boolean',
            'answers.q6' => 'nullable|boolean',
            'safety_plan' => 'nullable|string',
            'neuropsych_encounter_id' => 'nullable|exists:neuropsych_encounters,id',
        ]);

        // Throws a validation error (safety_plan) when moderate/high without a plan.
        $assessment = $this->risk->recordCssrs(
            (int) $data['patient_id'],
            $this->doctorId($request),
            $data['answers'],
            $data['safety_plan'] ?? null,
            $data['neuropsych_encounter_id'] ?? null,
        );

        AuditLogger::log('created', $assessment);

        $msg = $assessment->risk_level === 'high'
            ? $this->msg('HIGH suicide risk recorded — review the safety plan.', 'تم تسجيل خطر انتحار مرتفع — راجع خطة السلامة.')
            : $this->msg('Risk assessment saved', 'تم حفظ تقييم الخطر');

        return back()->with('success', $msg);
    }

    /** Sensitive read: a patient's risk-assessment history. Audited. */
    public function history(Request $request, Patient $patient): JsonResponse
    {
        AuditLogger::log('viewed_sensitive', $patient, ['context' => 'risk_assessments']);

        $items = RiskAssessment::where('patient_id', $patient->id)
            ->orderByDesc('assessed_at')
            ->get(['risk_level', 'tool', 'is_active', 'assessed_at', 'safety_plan']);

        return response()->json([
            'active' => $this->risk->activeRiskFor($patient->id),
            'history' => $items,
        ]);
    }
}

<?php

namespace App\Services\NeuroPsych;

use App\Models\RiskAssessment;
use App\Models\ScaleResult;
use Illuminate\Validation\ValidationException;

/**
 * NP3 — suicide-risk stratification (C-SSRS screener) + the clinic-wide "active
 * risk" signal that drives the patient-safety banner.
 *
 * The C-SSRS screener answers are 6 yes/no booleans keyed q1..q6:
 *   q1 wish to be dead · q2 suicidal thoughts · q3 thoughts with method ·
 *   q4 some intent · q5 intent with a plan · q6 behavior (recent/lifetime).
 * Stratification (conservative — escalates to the highest triggered band):
 *   q5 or q6 → high · q3 or q4 → moderate · q1 or q2 → low · else → none.
 */
class RiskAssessmentService
{
    public function stratifyCssrs(array $answers): string
    {
        $yes = fn ($k) => (bool) ($answers[$k] ?? false);

        if ($yes('q5') || $yes('q6')) {
            return 'high';
        }
        if ($yes('q3') || $yes('q4')) {
            return 'moderate';
        }
        if ($yes('q1') || $yes('q2')) {
            return 'low';
        }

        return 'none';
    }

    /**
     * Persist a C-SSRS risk assessment. A moderate/high level MUST carry a
     * safety plan — otherwise a validation error is thrown (patient safety).
     */
    public function recordCssrs(int $patientId, ?int $doctorId, array $answers, ?string $safetyPlan, ?int $encounterId = null): RiskAssessment
    {
        $level = $this->stratifyCssrs($answers);

        if (in_array($level, ['moderate', 'high'], true) && blank($safetyPlan)) {
            throw ValidationException::withMessages([
                'safety_plan' => app()->getLocale() === 'ar'
                    ? 'يجب توثيق خطة سلامة عند وجود خطر متوسط أو مرتفع.'
                    : 'A safety plan is required when risk is moderate or high.',
            ]);
        }

        return RiskAssessment::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'type' => 'suicide',
            'tool' => 'c-ssrs',
            'answers' => $answers,
            'risk_level' => $level,
            'safety_plan' => $safetyPlan,
            'is_active' => $level !== 'none',
            'neuropsych_encounter_id' => $encounterId,
            'assessed_at' => now(),
        ]);
    }

    /**
     * The active patient-safety signal for the banner. Aggregates the latest
     * active moderate/high risk assessment AND any recent flagged scale (e.g.
     * PHQ-9 item 9 > 0). Returns null when there is no active concern.
     *
     * @return array{level:string, reason_en:string, reason_ar:string}|null
     */
    public function activeRiskFor(int $patientId): ?array
    {
        $assessment = RiskAssessment::where('patient_id', $patientId)
            ->where('is_active', true)
            ->whereIn('risk_level', ['moderate', 'high'])
            ->orderByRaw("FIELD(risk_level,'high','moderate') ")
            ->orderByDesc('assessed_at')
            ->first();

        if ($assessment) {
            return [
                'level' => $assessment->risk_level,
                'reason_en' => 'Active '.$assessment->risk_level.'-risk suicide assessment (C-SSRS)',
                'reason_ar' => 'تقييم خطر انتحار نشط ('.$this->levelAr($assessment->risk_level).') — C-SSRS',
            ];
        }

        // Recent flagged measurement (PHQ-9 item 9, etc.) in the last 60 days.
        $flagged = ScaleResult::where('patient_id', $patientId)
            ->where('flag', true)
            ->where('taken_at', '>=', now()->subDays(60))
            ->orderByDesc('taken_at')
            ->first();

        if ($flagged) {
            return [
                'level' => 'moderate',
                'reason_en' => 'Recent questionnaire flagged a self-harm item ('.strtoupper($flagged->scale_key).')',
                'reason_ar' => 'استبيان حديث رفع علمًا لبند إيذاء النفس ('.strtoupper($flagged->scale_key).')',
            ];
        }

        return null;
    }

    private function levelAr(string $level): string
    {
        return match ($level) {
            'high' => 'مرتفع',
            'moderate' => 'متوسط',
            'low' => 'منخفض',
            default => 'لا يوجد',
        };
    }
}

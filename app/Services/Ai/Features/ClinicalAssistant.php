<?php

namespace App\Services\Ai\Features;

use App\Models\Setting;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;
use Illuminate\Support\Facades\DB;

/**
 * Clinical decision-SUPPORT for doctors. Every output is a draft suggestion that
 * the doctor must review and accept — never a final diagnosis or prescription,
 * and nothing is auto-saved. A disclaimer is prepended to every system prompt.
 * Patient identifiers are redacted by AiManager before any call.
 */
class ClinicalAssistant
{
    private const DISCLAIMER_AR = 'هذا مخرَج مولّد بالذكاء الاصطناعي للمساعدة فقط — ليس تشخيصًا أو وصفة نهائية. يجب أن يراجعه الطبيب ويعتمده.';

    private const DISCLAIMER_EN = 'AI-generated decision support only — not a final diagnosis or prescription. The physician must review and approve.';

    public function __construct(private readonly AiManager $ai) {}

    private function disclaimer(string $locale): string
    {
        return $locale === 'ar' ? self::DISCLAIMER_AR : self::DISCLAIMER_EN;
    }

    private function run(string $feature, string $system, string $user, array $options): AiResult
    {
        $locale = $options['locale'] ?? app()->getLocale();
        $messages = [
            ['role' => 'system', 'content' => $this->disclaimer($locale)."\n\n".$system],
            ['role' => 'user', 'content' => $user],
        ];
        $options['model'] = $options['model'] ?? Setting::get('ai_clinical_model', config('ai.defaults.clinical_model'));

        return $this->ai->generate($feature, $messages, $options);
    }

    /** D3 — one-glance summary of the patient file. */
    public function summary(int $patientId, array $options = []): AiResult
    {
        $ctx = $this->patientContext($patientId);
        $locale = $options['locale'] ?? app()->getLocale();
        $sys = $locale === 'ar'
            ? 'لخّص ملف المريض في نقاط موجزة للطبيب قبل الكشف: التركيبة، الحساسية، الأمراض المزمنة، آخر التشخيصات والأدوية والنقاط المهمة.'
            : 'Summarise the patient file into concise pre-visit bullet points: demographics, allergies, chronic conditions, recent diagnoses, current medications and red flags.';

        return $this->run('patient_summary', $sys, $ctx, $options);
    }

    /** D4 — SOAP note from brief notes. */
    public function soapNote(string $notes, array $options = []): AiResult
    {
        $locale = $options['locale'] ?? app()->getLocale();
        $sys = $locale === 'ar'
            ? 'حوّل الملاحظات المختصرة إلى ملاحظة SOAP منظمة (شكوى/فحص، تقييم، خطة).'
            : 'Convert the brief notes into a structured SOAP note (Subjective, Objective, Assessment, Plan).';

        return $this->run('soap_note', $sys, $notes, $options);
    }

    /**
     * NP — draft a psychiatry/neurology progress note from the structured Mental
     * Status Examination + brief notes. Non-diagnostic assistant; gated by the
     * np_note_assist feature flag.
     */
    public function npNote(string $context, array $options = []): AiResult
    {
        $locale = $options['locale'] ?? app()->getLocale();
        $sys = $locale === 'ar'
            ? 'أنت مساعد توثيق نفسي/عصبي (غير تشخيصي). صُغ من فحص الحالة العقلية والملاحظات المُدخلة مسوّدة ملاحظة سريرية منظّمة (الذاتي/الموضوعي/التقييم/الخطة) دون اختلاق معلومات.'
            : 'You are a psychiatry/neurology documentation assistant (non-diagnostic). From the Mental Status Examination and notes provided, draft a structured clinical note (Subjective/Objective/Assessment/Plan) without inventing information.';

        return $this->run('np_note_assist', $sys, $context, $options);
    }

    /** D5 — differential diagnosis from symptoms. */
    public function differential(string $symptoms, array $options = []): AiResult
    {
        $locale = $options['locale'] ?? app()->getLocale();
        $sys = $locale === 'ar'
            ? 'اقترح قائمة تشخيص تفريقي مرتبة حسب الاحتمال مع سبب موجز لكل احتمال. استشاري فقط.'
            : 'Suggest a ranked differential diagnosis list with a brief rationale for each. Advisory only.';

        return $this->run('differential_dx', $sys, $symptoms, $options);
    }

    /** D6 — ICD-10 coding suggestions. */
    public function icd10(string $diagnosis, array $options = []): AiResult
    {
        $sys = 'Suggest the most likely ICD-10 codes for the given diagnosis. Return code + description. Advisory only.';

        return $this->run('icd10_suggest', $sys, $diagnosis, $options);
    }

    /** D8 — medication suggestions from a diagnosis. */
    public function prescriptionSuggest(string $diagnosis, array $options = []): AiResult
    {
        $locale = $options['locale'] ?? app()->getLocale();
        $extra = isset($options['patient']) ? "\nPatient: ".$options['patient'] : '';
        $sys = $locale === 'ar'
            ? 'اقترح أدوية مناسبة مع الجرعة والمدة بناءً على التشخيص. اقتراح للمراجعة فقط — يجب فحص التعارض والحساسية قبل الاعتماد.'
            : 'Suggest appropriate medications with dosage and duration for the diagnosis. Suggestion only — drug-interaction and allergy checks are required before approval.';

        return $this->run('prescription_suggest', $sys, $diagnosis.$extra, $options);
    }

    /** D26 — medical report / referral letter / sick-leave certificate. */
    public function report(string $type, string $content, array $options = []): AiResult
    {
        $locale = $options['locale'] ?? app()->getLocale();
        $sys = $locale === 'ar'
            ? "اكتب {$type} طبيًا رسميًا ومنظمًا بناءً على المعطيات."
            : "Write a formal, well-structured {$type} based on the details.";

        return $this->run('medical_report', $sys, $content, $options);
    }

    /** Gather a minimal, useful patient context string for summaries. */
    private function patientContext(int $patientId): string
    {
        $p = DB::table('patients')->find($patientId);
        if (! $p) {
            return 'No patient record found.';
        }

        $age = $p->date_of_birth ? (int) \Carbon\Carbon::parse($p->date_of_birth)->age.'y' : 'unknown';
        $lines = [
            'Gender: '.($p->gender ?? '-').', Age: '.$age.', Blood: '.($p->blood_type ?? '-'),
            'Allergies: '.($p->allergies ?: 'none recorded'),
            'Chronic conditions: '.($p->chronic_conditions ?: 'none recorded'),
        ];

        $visits = DB::table('visits')->where('patient_id', $patientId)
            ->whereNotNull('diagnosis')->orderByDesc('visit_date')->limit(5)
            ->pluck('diagnosis')->all();
        if ($visits) {
            $lines[] = 'Recent diagnoses: '.implode('; ', $visits);
        }

        $meds = DB::table('prescription_items')
            ->join('prescriptions', 'prescription_items.prescription_id', '=', 'prescriptions.id')
            ->where('prescriptions.patient_id', $patientId)
            ->orderByDesc('prescription_items.created_at')->limit(10)
            ->pluck('prescription_items.medication_name')->unique()->all();
        if ($meds) {
            $lines[] = 'Recent medications: '.implode(', ', $meds);
        }

        return implode("\n", $lines);
    }
}

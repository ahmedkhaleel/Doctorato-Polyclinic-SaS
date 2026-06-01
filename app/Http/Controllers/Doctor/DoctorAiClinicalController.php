<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Setting;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\Features\ClinicalAssistant;
use App\Services\Ai\Features\DrugInteractionChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Wave-3 clinical decision support for the doctor panel. Every endpoint is a
 * suggestion the doctor reviews; nothing is auto-saved. Patient-bound actions
 * enforce consent (when required) and write a medical-data access-log entry.
 */
class DoctorAiClinicalController extends BaseDoctorController
{
    public function index(): Response
    {
        return Inertia::render('Doctor/AiClinical', [
            'consentRequired' => (bool) Setting::get('ai_patient_consent_required', true),
        ]);
    }

    public function summary(Request $request, ClinicalAssistant $assistant): JsonResponse
    {
        $v = $request->validate(['patient_id' => 'required|integer|exists:patients,id', 'consent' => 'boolean']);
        if ($block = $this->consentBlock($request, (int) $v['patient_id'])) {
            return $block;
        }
        $this->audit($request, (int) $v['patient_id'], 'patient_summary');

        return $this->wrap(fn () => $assistant->summary((int) $v['patient_id'], $this->opts($request)));
    }

    public function soap(Request $request, ClinicalAssistant $assistant): JsonResponse
    {
        $v = $request->validate(['notes' => 'required|string|max:4000']);

        return $this->wrap(fn () => $assistant->soapNote($v['notes'], $this->opts($request)));
    }

    public function differential(Request $request, ClinicalAssistant $assistant): JsonResponse
    {
        $v = $request->validate(['symptoms' => 'required|string|max:4000']);

        return $this->wrap(fn () => $assistant->differential($v['symptoms'], $this->opts($request)));
    }

    public function icd10(Request $request, ClinicalAssistant $assistant): JsonResponse
    {
        $v = $request->validate(['diagnosis' => 'required|string|max:1000']);

        return $this->wrap(fn () => $assistant->icd10($v['diagnosis'], $this->opts($request)));
    }

    public function prescription(Request $request, ClinicalAssistant $assistant): JsonResponse
    {
        $v = $request->validate(['diagnosis' => 'required|string|max:2000', 'patient_age' => 'nullable|string|max:20']);
        $opts = $this->opts($request);
        if (! empty($v['patient_age'])) {
            $opts['patient'] = 'age '.$v['patient_age'];
        }

        return $this->wrap(fn () => $assistant->prescriptionSuggest($v['diagnosis'], $opts));
    }

    /** D9 — mandatory drug interaction + allergy check. */
    public function drugCheck(Request $request, DrugInteractionChecker $checker): JsonResponse
    {
        $v = $request->validate([
            'medications' => 'required|array|min:1',
            'medications.*' => 'string|max:200',
            'allergies' => 'nullable|string|max:1000',
            'current_meds' => 'nullable|string|max:1000',
        ]);

        return $this->wrap(fn () => $checker->check(
            $v['medications'], $v['allergies'] ?? '', $v['current_meds'] ?? '', $this->opts($request)
        ));
    }

    public function report(Request $request, ClinicalAssistant $assistant): JsonResponse
    {
        $v = $request->validate([
            'type' => 'required|string|max:60',
            'content' => 'required|string|max:4000',
        ]);

        return $this->wrap(fn () => $assistant->report($v['type'], $v['content'], $this->opts($request)));
    }

    // ─── helpers ─────────────────────────────────────────────
    private function opts(Request $request): array
    {
        return [
            'locale' => app()->getLocale(),
            'rate_key' => 'doctor:'.$request->user()?->id,
            'actor' => ['type' => 'doctor', 'id' => $request->user()?->id],
        ];
    }

    private function consentBlock(Request $request, int $patientId): ?JsonResponse
    {
        if (Setting::get('ai_patient_consent_required', true) && ! $request->boolean('consent')) {
            return response()->json([
                'ok' => false,
                'reason' => 'consent_required',
                'message' => app()->getLocale() === 'ar'
                    ? 'يلزم تأكيد موافقة المريض على المعالجة بالذكاء الاصطناعي.'
                    : 'Patient consent for AI processing is required.',
            ], 422);
        }

        return null;
    }

    private function audit(Request $request, int $patientId, string $category): void
    {
        try {
            DB::table('medical_data_access_logs')->insert([
                'user_id' => $request->user()->id,
                'patient_id' => $patientId,
                'access_type' => 'ai',
                'data_category' => $category,
                'panel' => 'doctor',
                'ip_address' => $request->ip(),
                'reason' => 'AI clinical assist',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Auditing must never break the feature.
        }
    }

    private function wrap(callable $cb): JsonResponse
    {
        try {
            $result = $cb();
        } catch (AiUnavailableException $e) {
            return response()->json(['ok' => false, 'reason' => $e->reason, 'message' => $this->reasonMessage($e->reason)], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'reason' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'text' => $result->text, 'model' => $result->model, 'tokens' => $result->totalTokens()]);
    }

    private function reasonMessage(string $reason): string
    {
        return match ($reason) {
            'disabled' => 'الذكاء الاصطناعي معطّل. / AI is disabled.',
            'feature_off' => 'هذه الميزة السريرية غير مفعّلة. / This clinical feature is off.',
            'over_budget' => 'تم بلوغ ميزانية الذكاء الاصطناعي. / AI budget reached.',
            'rate_limited' => 'طلبات كثيرة، تمهّل قليلًا. / Too many requests.',
            'no_key' => 'مفتاح OpenAI غير مُهيّأ. / OpenAI key not configured.',
            default => 'الذكاء الاصطناعي غير متاح. / AI unavailable.',
        };
    }
}

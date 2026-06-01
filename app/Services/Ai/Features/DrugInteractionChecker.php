<?php

namespace App\Services\Ai\Features;

use App\Models\Setting;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;

/**
 * D9 — the mandatory safety check that must run before any AI-suggested
 * prescription is accepted. Cross-checks proposed medications against the
 * patient's recorded allergies and current medications, and flags interactions.
 */
class DrugInteractionChecker
{
    public function __construct(private readonly AiManager $ai) {}

    /**
     * @param  array<int,string>  $medications  proposed drugs
     */
    public function check(array $medications, string $allergies = '', string $currentMeds = '', array $options = []): AiResult
    {
        $locale = $options['locale'] ?? app()->getLocale();

        $sys = $locale === 'ar'
            ? 'أنت صيدلي إكلينيكي. افحص الأدوية المقترحة بحثًا عن: (1) تعارض مع حساسية المريض، (2) تعارض دوائي مع أدويته الحالية، (3) تعارضات بين الأدوية المقترحة نفسها. '
                .'أبرِز كل تحذير بوضوح وبدرجة خطورة (خطير/متوسط/منخفض). إن لم توجد مخاطر فاذكر ذلك صراحةً. هذا فحص مساعد ولا يغني عن حكم الطبيب.'
            : 'You are a clinical pharmacist. Check the proposed medications for: (1) conflicts with the patient allergies, (2) interactions with current medications, (3) interactions among the proposed drugs themselves. '
                .'Flag each warning clearly with a severity (high/moderate/low). If none, say so explicitly. Advisory check — does not replace physician judgement.';

        $user = "Proposed medications:\n- ".implode("\n- ", $medications)
            ."\n\nPatient allergies: ".($allergies ?: 'none recorded')
            ."\nCurrent medications: ".($currentMeds ?: 'none recorded');

        $options['model'] = $options['model'] ?? Setting::get('ai_clinical_model', config('ai.defaults.clinical_model'));

        return $this->ai->generate('drug_interaction', [
            ['role' => 'system', 'content' => $sys],
            ['role' => 'user', 'content' => $user],
        ], $options);
    }
}

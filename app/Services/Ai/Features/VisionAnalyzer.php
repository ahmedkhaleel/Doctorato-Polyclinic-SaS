<?php

namespace App\Services\Ai\Features;

use App\Models\Setting;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;

/**
 * Vision analysis (GPT-4o). Advisory only — supports dental X-rays, dermatology
 * photos and insurance-card OCR. Clinical modes carry a non-diagnostic disclaimer.
 */
class VisionAnalyzer
{
    private const MODES = [
        'dental_xray_vision' => [
            'feature' => 'dental_xray_vision',
            'ar' => 'حلّل صورة أشعة الأسنان بشكل استشاري: أبرِز ما قد يلفت النظر (تسوّس محتمل، فقد عظمي، تكلّسات) مع التأكيد أن هذا للمساعدة وليس تشخيصًا.',
            'en' => 'Analyse this dental X-ray in an advisory way: note possible findings (caries, bone loss, calcifications). State this is decision-support, not a diagnosis.',
            'clinical' => true,
        ],
        'derma_image_vision' => [
            'feature' => 'derma_image_vision',
            'ar' => 'قدّم تقييمًا مبدئيًا استشاريًا لصورة الحالة الجلدية (الوصف، الاحتمالات، التوصية بالمتابعة). ليس تشخيصًا نهائيًا.',
            'en' => 'Give an advisory preliminary assessment of this skin image (description, possibilities, follow-up recommendation). Not a final diagnosis.',
            'clinical' => true,
        ],
        'insurance_ocr' => [
            'feature' => 'insurance_ocr',
            'ar' => 'استخرج بيانات بطاقة التأمين وأعِدها كـ JSON فقط (بدون أي نص آخر) بالمفاتيح: member_id, policy_number, card_number, company_name, plan_name, principal_name, expiry_date (بصيغة YYYY-MM-DD). ضع null لأي حقل غير ظاهر.',
            'en' => 'Extract the insurance card data and return ONLY a JSON object (no other text) with keys: member_id, policy_number, card_number, company_name, plan_name, principal_name, expiry_date (as YYYY-MM-DD). Use null for any field not visible.',
            'clinical' => false,
        ],
    ];

    public function __construct(private readonly AiManager $ai) {}

    public function analyze(string $mode, string $imageDataUri, string $extra = '', array $options = []): AiResult
    {
        $cfg = self::MODES[$mode] ?? throw new \InvalidArgumentException("Unknown vision mode: {$mode}");
        $locale = $options['locale'] ?? app()->getLocale();
        $prompt = $cfg[$locale === 'ar' ? 'ar' : 'en'].($extra ? "\n".$extra : '');

        $system = $cfg['clinical']
            ? ($locale === 'ar' ? 'هذا مخرَج مساعد بالذكاء الاصطناعي وليس تشخيصًا. يجب اعتماد الطبيب.' : 'AI decision-support, not a diagnosis. Physician must approve.')
            : ($locale === 'ar' ? 'استخرج البيانات بدقة.' : 'Extract data accurately.');

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageDataUri]],
            ]],
        ];

        $options['model'] = $options['model'] ?? Setting::get('ai_vision_model', config('ai.defaults.vision_model'));

        return $this->ai->generate($cfg['feature'], $messages, $options);
    }
}

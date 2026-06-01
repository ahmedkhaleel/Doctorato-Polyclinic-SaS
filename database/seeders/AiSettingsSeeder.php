<?php

namespace Database\Seeders;

use App\Models\AiFeatureFlag;
use App\Models\AiPromptTemplate;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Reference data for the AI layer: default settings (AI OFF by default),
 * the feature-flag registry (all disabled), and default ar/en prompt templates.
 * Idempotent — safe to re-run. No secrets are seeded.
 */
class AiSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Default settings (group=ai). AI stays OFF until the admin enables it.
        $defaults = [
            'ai_enabled' => '0',
            'ai_provider' => 'openai',
            'ai_default_model' => 'gpt-4o-mini',
            'ai_clinical_model' => 'gpt-4o',
            'ai_vision_model' => 'gpt-4o',
            'ai_embedding_model' => 'text-embedding-3-small',
            'ai_transcribe_model' => 'whisper-1',
            'ai_monthly_budget_usd' => '50',
            'ai_budget_alert_pct' => '80',
            'ai_rate_limit_per_min' => '20',
            'ai_phi_redaction' => '1',
            'ai_log_prompts' => '0',
            'ai_patient_consent_required' => '1',
        ];
        foreach ($defaults as $key => $value) {
            if (Setting::where('key', $key)->where('branch_id', Setting::GLOBAL_BRANCH)->doesntExist()) {
                Setting::set($key, $value, 'ai');
            }
        }

        // ── Feature-flag registry (all disabled by default).
        $features = [
            // Wave 1
            ['seo_content', 'توليد محتوى SEO', 'SEO Content', 'wave1', 1],
            ['comms_drafting', 'صياغة الرسائل', 'Comms Drafting', 'wave1', 2],
            ['translation', 'الترجمة ar↔en', 'Translation', 'wave1', 3],
            ['lead_reply', 'ردود العملاء المحتملين', 'Lead Reply', 'wave1', 4],
            ['campaign_copy', 'نصوص الحملات', 'Campaign Copy', 'wave1', 5],
            // Wave 2 — patient + doctor text
            ['patient_assistant', 'مساعد المريض', 'Patient Assistant', 'patient', 10],
            ['satisfaction_sentiment', 'تحليل مشاعر الرضا', 'Satisfaction Sentiment', 'patient', 11],
            ['followup_message', 'رسائل المتابعة', 'Follow-up Message', 'patient', 12],
            ['doctor_review_reply', 'رد تقييمات الطبيب', 'Doctor Review Reply', 'patient', 13],
            ['doctor_bio', 'سيرة الطبيب المهنية', 'Doctor Bio', 'patient', 14],
            // Wave 3 — clinical (doctor)
            ['patient_summary', 'تلخيص ملف المريض', 'Patient Summary', 'clinical', 20],
            ['soap_note', 'ملاحظة SOAP', 'SOAP Note', 'clinical', 21],
            ['differential_dx', 'تشخيص تفريقي', 'Differential Diagnosis', 'clinical', 22],
            ['icd10_suggest', 'اقتراح ICD-10', 'ICD-10 Suggest', 'clinical', 23],
            ['prescription_suggest', 'اقتراح الوصفات', 'Prescription Suggest', 'clinical', 24],
            ['drug_interaction', 'فاحص تعارض الأدوية', 'Drug Interaction Check', 'clinical', 25],
            ['medical_report', 'تقارير وإحالات طبية', 'Medical Report', 'clinical', 26],
            // Wave 4 — vision/voice
            ['dental_xray_vision', 'تحليل أشعة الأسنان', 'Dental X-ray Vision', 'vision', 30],
            ['derma_image_vision', 'تقييم صور الجلدية', 'Derma Image Vision', 'vision', 31],
            ['consult_transcription', 'تفريغ الاستشارة', 'Consult Transcription', 'vision', 32],
            ['nl_analytics', 'تحليلات بلغة طبيعية', 'NL Analytics', 'vision', 33],
            ['insurance_ocr', 'استخراج بيانات التأمين', 'Insurance OCR', 'vision', 34],
        ];
        foreach ($features as [$key, $ar, $en, $group, $order]) {
            AiFeatureFlag::firstOrCreate(
                ['key' => $key],
                ['enabled' => false, 'label_ar' => $ar, 'label_en' => $en, 'group' => $group, 'display_order' => $order],
            );
        }

        // ── Default prompt templates (ar/en) for a few core features.
        $prompts = [
            ['translation', 'ar', 'أنت مترجم طبي محترف. ترجم النص بدقة مع الحفاظ على المصطلحات الطبية.', 'ترجم النص التالي إلى {{target}}:\n\n{{text}}'],
            ['translation', 'en', 'You are a professional medical translator. Translate accurately, preserving medical terminology.', 'Translate the following text to {{target}}:\n\n{{text}}'],
            ['comms_drafting', 'ar', 'أنت مساعد عيادة طبية. اكتب رسائل مهنية ودودة وموجزة بالعربية الفصحى المبسطة.', 'اكتب رسالة {{channel}} للمريض حول: {{topic}}'],
            ['seo_content', 'ar', 'أنت كاتب محتوى طبي متخصص في تحسين محركات البحث (SEO) بالعربية.', 'اكتب {{type}} حول: {{topic}}. اجعله محسّنًا لمحركات البحث.'],
            ['soap_note', 'ar', 'أنت مساعد توثيق طبي. حوّل ملاحظات الطبيب المختصرة إلى ملاحظة SOAP منظمة. هذا اقتراح للمساعدة وليس تشخيصًا نهائيًا.', 'ملاحظات الطبيب:\n{{notes}}\n\nأنشئ ملاحظة SOAP منظمة.'],
            ['lead_reply', 'ar', 'أنت مساعد مبيعات ودود لعيادة طبية. ردّ على العملاء المحتملين بإقناع ومساعدة دون أي ادعاءات طبية.', 'كتب عميل محتمل:\n{{message}}\n\nاكتب ردًا بنبرة {{tone}}.'],
            ['campaign_copy', 'ar', 'أنت كاتب إعلانات تسويق صحي. اكتب نصوص حملات ملتزمة وجذابة.', 'اكتب نص حملة على {{channel}} لـ: {{product}}. الهدف: {{goal}}.'],
        ];
        foreach ($prompts as [$feature, $locale, $sys, $tpl]) {
            AiPromptTemplate::firstOrCreate(
                ['feature' => $feature, 'locale' => $locale],
                ['system_prompt' => $sys, 'user_template' => $tpl, 'version' => 1, 'is_active' => true],
            );
        }
    }
}

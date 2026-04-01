<?php

namespace Database\Seeders;

use App\Models\DentalPrescriptionTemplate;
use App\Models\DentalPrescriptionTemplateItem;
use Illuminate\Database\Seeder;

class DentalPrescriptionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ─── Extraction ───────────────────────────────────────
            [
                'name_ar' => 'وصفة بعد الخلع',
                'name_en' => 'Post-Extraction Prescription',
                'treatment_type' => 'extraction',
                'diagnosis_ar' => 'خلع سن',
                'diagnosis_en' => 'Tooth extraction',
                'notes_ar' => 'تجنب الأكل على مكان الخلع لمدة ساعتين. لا تستخدم الشفاطة. تجنب البصق بقوة.',
                'notes_en' => 'Avoid eating on extraction site for 2 hours. No straw use. Avoid forceful spitting.',
                'auto_apply' => true,
                'sort_order' => 1,
                'items' => [
                    ['medication_name' => 'Amoxicillin 500mg', 'dosage' => '500mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 1],
                    ['medication_name' => 'Metronidazole 500mg', 'dosage' => '500mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 2],
                    ['medication_name' => 'Ibuprofen 400mg', 'dosage' => '400mg', 'frequency' => 'Three times daily', 'duration' => '3 days', 'instructions_ar' => 'بعد الأكل عند الحاجة', 'instructions_en' => 'After meals as needed', 'sort_order' => 3],
                    ['medication_name' => 'Chlorhexidine Mouthwash 0.12%', 'dosage' => '15ml', 'frequency' => 'Twice daily', 'duration' => '7 days', 'instructions_ar' => 'مضمضة لمدة 30 ثانية - يبدأ بعد 24 ساعة من الخلع', 'instructions_en' => 'Rinse for 30 seconds - start 24h after extraction', 'sort_order' => 4],
                ],
            ],

            // ─── Surgical Extraction ──────────────────────────────
            [
                'name_ar' => 'وصفة بعد الخلع الجراحي',
                'name_en' => 'Post-Surgical Extraction Prescription',
                'treatment_type' => 'surgical_extraction',
                'diagnosis_ar' => 'خلع جراحي',
                'diagnosis_en' => 'Surgical tooth extraction',
                'notes_ar' => 'كمادات باردة على الخد لمدة 20 دقيقة كل ساعة خلال أول 24 ساعة. طعام لين لمدة 3 أيام.',
                'notes_en' => 'Cold compress on cheek 20min/hour for first 24h. Soft diet for 3 days.',
                'auto_apply' => true,
                'sort_order' => 2,
                'items' => [
                    ['medication_name' => 'Augmentin 1g', 'dosage' => '1g', 'frequency' => 'Twice daily', 'duration' => '7 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 1],
                    ['medication_name' => 'Metronidazole 500mg', 'dosage' => '500mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 2],
                    ['medication_name' => 'Diclofenac Sodium 50mg', 'dosage' => '50mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 3],
                    ['medication_name' => 'Paracetamol 500mg', 'dosage' => '1000mg', 'frequency' => 'Every 6 hours', 'duration' => '3 days', 'instructions_ar' => 'عند الحاجة للألم الشديد - يؤخذ مع الديكلوفيناك', 'instructions_en' => 'As needed for severe pain - can combine with Diclofenac', 'sort_order' => 4],
                    ['medication_name' => 'Chlorhexidine Mouthwash 0.12%', 'dosage' => '15ml', 'frequency' => 'Twice daily', 'duration' => '10 days', 'instructions_ar' => 'مضمضة لمدة 30 ثانية - يبدأ بعد 24 ساعة من الجراحة', 'instructions_en' => 'Rinse for 30 seconds - start 24h post-surgery', 'sort_order' => 5],
                ],
            ],

            // ─── Root Canal ───────────────────────────────────────
            [
                'name_ar' => 'وصفة بعد علاج العصب',
                'name_en' => 'Post-Root Canal Prescription',
                'treatment_type' => 'root_canal',
                'diagnosis_ar' => 'علاج عصب',
                'diagnosis_en' => 'Root canal treatment',
                'notes_ar' => 'تجنب المضغ على السن المعالج حتى تركيب التاج النهائي.',
                'notes_en' => 'Avoid chewing on treated tooth until final crown placement.',
                'auto_apply' => true,
                'sort_order' => 3,
                'items' => [
                    ['medication_name' => 'Augmentin 1g', 'dosage' => '1g', 'frequency' => 'Twice daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 1],
                    ['medication_name' => 'Ibuprofen 400mg', 'dosage' => '400mg', 'frequency' => 'Three times daily', 'duration' => '3 days', 'instructions_ar' => 'بعد الأكل عند الحاجة', 'instructions_en' => 'After meals as needed', 'sort_order' => 2],
                ],
            ],

            // ─── Implant ─────────────────────────────────────────
            [
                'name_ar' => 'وصفة بعد الزراعة',
                'name_en' => 'Post-Implant Prescription',
                'treatment_type' => 'implant',
                'diagnosis_ar' => 'زراعة أسنان',
                'diagnosis_en' => 'Dental implant placement',
                'notes_ar' => 'طعام لين لمدة أسبوع. كمادات باردة أول 48 ساعة. تجنب التدخين تماماً.',
                'notes_en' => 'Soft diet for 1 week. Cold compress first 48h. Absolutely no smoking.',
                'auto_apply' => true,
                'sort_order' => 4,
                'items' => [
                    ['medication_name' => 'Augmentin 1g', 'dosage' => '1g', 'frequency' => 'Twice daily', 'duration' => '7 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 1],
                    ['medication_name' => 'Metronidazole 500mg', 'dosage' => '500mg', 'frequency' => 'Three times daily', 'duration' => '7 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 2],
                    ['medication_name' => 'Diclofenac Sodium 50mg', 'dosage' => '50mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 3],
                    ['medication_name' => 'Dexamethasone 0.5mg', 'dosage' => '4mg', 'frequency' => 'Once daily', 'duration' => '3 days', 'instructions_ar' => 'صباحاً بعد الفطور', 'instructions_en' => 'Morning after breakfast', 'sort_order' => 4],
                    ['medication_name' => 'Chlorhexidine Mouthwash 0.12%', 'dosage' => '15ml', 'frequency' => 'Twice daily', 'duration' => '14 days', 'instructions_ar' => 'مضمضة لمدة 30 ثانية - يبدأ بعد 24 ساعة', 'instructions_en' => 'Rinse for 30 seconds - start after 24h', 'sort_order' => 5],
                ],
            ],

            // ─── Gum Treatment ────────────────────────────────────
            [
                'name_ar' => 'وصفة علاج اللثة',
                'name_en' => 'Gum Treatment Prescription',
                'treatment_type' => 'gum_treatment',
                'diagnosis_ar' => 'التهاب لثة',
                'diagnosis_en' => 'Periodontal treatment',
                'notes_ar' => 'استخدام فرشاة ناعمة. تنظيف بالخيط يومياً.',
                'notes_en' => 'Use soft toothbrush. Daily flossing.',
                'auto_apply' => true,
                'sort_order' => 5,
                'items' => [
                    ['medication_name' => 'Metronidazole 500mg', 'dosage' => '500mg', 'frequency' => 'Three times daily', 'duration' => '7 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 1],
                    ['medication_name' => 'Chlorhexidine Mouthwash 0.2%', 'dosage' => '15ml', 'frequency' => 'Twice daily', 'duration' => '14 days', 'instructions_ar' => 'مضمضة لمدة 30 ثانية', 'instructions_en' => 'Rinse for 30 seconds', 'sort_order' => 2],
                    ['medication_name' => 'Ibuprofen 400mg', 'dosage' => '400mg', 'frequency' => 'Twice daily', 'duration' => '3 days', 'instructions_ar' => 'بعد الأكل عند الحاجة', 'instructions_en' => 'After meals as needed', 'sort_order' => 3],
                ],
            ],

            // ─── Bone Graft ───────────────────────────────────────
            [
                'name_ar' => 'وصفة بعد ترقيع العظم',
                'name_en' => 'Post-Bone Graft Prescription',
                'treatment_type' => 'bone_graft',
                'diagnosis_ar' => 'ترقيع عظم',
                'diagnosis_en' => 'Bone graft procedure',
                'notes_ar' => 'طعام لين لمدة أسبوع. عدم لمس منطقة الجراحة باللسان. تجنب التدخين.',
                'notes_en' => 'Soft diet for 1 week. Do not touch surgical area with tongue. No smoking.',
                'auto_apply' => true,
                'sort_order' => 6,
                'items' => [
                    ['medication_name' => 'Augmentin 1g', 'dosage' => '1g', 'frequency' => 'Twice daily', 'duration' => '7 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 1],
                    ['medication_name' => 'Metronidazole 500mg', 'dosage' => '500mg', 'frequency' => 'Three times daily', 'duration' => '7 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 2],
                    ['medication_name' => 'Diclofenac Sodium 50mg', 'dosage' => '50mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 3],
                    ['medication_name' => 'Dexamethasone 0.5mg', 'dosage' => '4mg', 'frequency' => 'Once daily', 'duration' => '3 days', 'instructions_ar' => 'صباحاً بعد الفطور', 'instructions_en' => 'Morning after breakfast', 'sort_order' => 4],
                    ['medication_name' => 'Chlorhexidine Mouthwash 0.12%', 'dosage' => '15ml', 'frequency' => 'Twice daily', 'duration' => '14 days', 'instructions_ar' => 'مضمضة بلطف - يبدأ بعد 24 ساعة', 'instructions_en' => 'Gentle rinse - start after 24h', 'sort_order' => 5],
                ],
            ],

            // ─── Sinus Lift ───────────────────────────────────────
            [
                'name_ar' => 'وصفة بعد رفع الجيب الأنفي',
                'name_en' => 'Post-Sinus Lift Prescription',
                'treatment_type' => 'sinus_lift',
                'diagnosis_ar' => 'رفع جيب أنفي',
                'diagnosis_en' => 'Sinus lift procedure',
                'notes_ar' => 'لا تنفخ أنفك بقوة لمدة أسبوعين. تجنب العطس بفم مغلق. نم ورأسك مرتفع.',
                'notes_en' => 'Do not blow nose forcefully for 2 weeks. Avoid sneezing with mouth closed. Sleep with head elevated.',
                'auto_apply' => true,
                'sort_order' => 7,
                'items' => [
                    ['medication_name' => 'Augmentin 1g', 'dosage' => '1g', 'frequency' => 'Twice daily', 'duration' => '10 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 1],
                    ['medication_name' => 'Diclofenac Sodium 50mg', 'dosage' => '50mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions_ar' => 'بعد الأكل', 'instructions_en' => 'After meals', 'sort_order' => 2],
                    ['medication_name' => 'Dexamethasone 0.5mg', 'dosage' => '4mg', 'frequency' => 'Once daily', 'duration' => '5 days', 'instructions_ar' => 'صباحاً بعد الفطور', 'instructions_en' => 'Morning after breakfast', 'sort_order' => 3],
                    ['medication_name' => 'Oxymetazoline Nasal Spray 0.05%', 'dosage' => '2 sprays', 'frequency' => 'Twice daily', 'duration' => '5 days', 'instructions_ar' => 'بخاخ أنف في كل فتحة', 'instructions_en' => 'Nasal spray in each nostril', 'sort_order' => 4],
                    ['medication_name' => 'Chlorhexidine Mouthwash 0.12%', 'dosage' => '15ml', 'frequency' => 'Twice daily', 'duration' => '14 days', 'instructions_ar' => 'مضمضة بلطف', 'instructions_en' => 'Gentle rinse', 'sort_order' => 5],
                ],
            ],

            // ─── Scaling (Deep Cleaning) ──────────────────────────
            [
                'name_ar' => 'وصفة بعد التقليح',
                'name_en' => 'Post-Scaling Prescription',
                'treatment_type' => 'scaling',
                'diagnosis_ar' => 'تقليح وتنظيف عميق',
                'diagnosis_en' => 'Scaling and root planing',
                'notes_ar' => 'حساسية مؤقتة للأسنان طبيعية وتختفي خلال أسبوع.',
                'notes_en' => 'Temporary tooth sensitivity is normal and resolves within a week.',
                'auto_apply' => false,
                'sort_order' => 8,
                'items' => [
                    ['medication_name' => 'Chlorhexidine Mouthwash 0.12%', 'dosage' => '15ml', 'frequency' => 'Twice daily', 'duration' => '7 days', 'instructions_ar' => 'مضمضة لمدة 30 ثانية', 'instructions_en' => 'Rinse for 30 seconds', 'sort_order' => 1],
                    ['medication_name' => 'Ibuprofen 400mg', 'dosage' => '400mg', 'frequency' => 'As needed (PRN)', 'duration' => '3 days', 'instructions_ar' => 'بعد الأكل عند الألم', 'instructions_en' => 'After meals for pain', 'sort_order' => 2],
                ],
            ],

            // ─── Filling (no auto-apply, mild procedure) ─────────
            [
                'name_ar' => 'وصفة بعد الحشو',
                'name_en' => 'Post-Filling Prescription',
                'treatment_type' => 'filling',
                'diagnosis_ar' => 'حشو سن',
                'diagnosis_en' => 'Dental filling',
                'notes_ar' => 'تجنب الأكل لمدة ساعة. قد تستمر الحساسية لبضعة أيام.',
                'notes_en' => 'Avoid eating for 1 hour. Sensitivity may persist for a few days.',
                'auto_apply' => false,
                'sort_order' => 9,
                'items' => [
                    ['medication_name' => 'Ibuprofen 400mg', 'dosage' => '400mg', 'frequency' => 'As needed (PRN)', 'duration' => '3 days', 'instructions_ar' => 'بعد الأكل عند الألم', 'instructions_en' => 'After meals for pain', 'sort_order' => 1],
                ],
            ],

            // ─── Crown Preparation ───────────────────────────────
            [
                'name_ar' => 'وصفة بعد تحضير التاج',
                'name_en' => 'Post-Crown Prep Prescription',
                'treatment_type' => 'crown',
                'diagnosis_ar' => 'تحضير تاج',
                'diagnosis_en' => 'Crown preparation',
                'notes_ar' => 'حافظ على التاج المؤقت. تجنب الأطعمة اللزجة والصلبة.',
                'notes_en' => 'Keep temporary crown in place. Avoid sticky and hard foods.',
                'auto_apply' => false,
                'sort_order' => 10,
                'items' => [
                    ['medication_name' => 'Ibuprofen 400mg', 'dosage' => '400mg', 'frequency' => 'As needed (PRN)', 'duration' => '3 days', 'instructions_ar' => 'بعد الأكل عند الألم', 'instructions_en' => 'After meals for pain', 'sort_order' => 1],
                    ['medication_name' => 'Sensodyne Toothpaste', 'dosage' => 'Apply', 'frequency' => 'Twice daily', 'duration' => 'Ongoing', 'instructions_ar' => 'استخدام معجون للأسنان الحساسة', 'instructions_en' => 'Use sensitivity toothpaste', 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            $items = $templateData['items'];
            unset($templateData['items']);

            $template = DentalPrescriptionTemplate::create($templateData);

            foreach ($items as $item) {
                $item['template_id'] = $template->id;
                DentalPrescriptionTemplateItem::create($item);
            }
        }
    }
}

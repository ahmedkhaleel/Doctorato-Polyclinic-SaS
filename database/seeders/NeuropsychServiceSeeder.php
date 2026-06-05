<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Bookable service catalog for the psychiatry & neurology modules, so that
 * enabling either module gives patients/staff real services to book (mirrors
 * ObgynServiceSeeder). Idempotent (updateOrCreate by slug).
 */
class NeuropsychServiceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'الطب النفسي',
                'name_en' => 'Psychiatry',
                'slug' => 'psychiatry',
                'module' => 'psychiatry',
                'display_order' => 1,
                'services' => [
                    ['name_ar' => 'كشف نفسي', 'name_en' => 'Psychiatric Consultation', 'short_desc_ar' => 'تقييم نفسي شامل وتشخيص ووضع خطة علاجية', 'short_desc_en' => 'Comprehensive psychiatric assessment, diagnosis and treatment plan'],
                    ['name_ar' => 'متابعة نفسية', 'name_en' => 'Psychiatry Follow-up', 'short_desc_ar' => 'متابعة دورية للحالة وضبط الأدوية', 'short_desc_en' => 'Periodic review and medication adjustment'],
                    ['name_ar' => 'جلسة علاج نفسي', 'name_en' => 'Psychotherapy Session', 'short_desc_ar' => 'جلسة علاج نفسي فردية (CBT وغيرها)', 'short_desc_en' => 'Individual psychotherapy session (CBT and others)'],
                ],
            ],
            [
                'name_ar' => 'طب الأعصاب',
                'name_en' => 'Neurology',
                'slug' => 'neurology',
                'module' => 'neurology',
                'display_order' => 2,
                'services' => [
                    ['name_ar' => 'كشف أعصاب', 'name_en' => 'Neurology Consultation', 'short_desc_ar' => 'فحص عصبي شامل وتشخيص أمراض الجهاز العصبي', 'short_desc_en' => 'Full neurological examination and diagnosis'],
                    ['name_ar' => 'متابعة أعصاب', 'name_en' => 'Neurology Follow-up', 'short_desc_ar' => 'متابعة دورية وضبط خطة العلاج', 'short_desc_en' => 'Periodic follow-up and treatment adjustment'],
                    ['name_ar' => 'رسم مخ (EEG)', 'name_en' => 'EEG', 'short_desc_ar' => 'تخطيط كهربية الدماغ لتشخيص الصرع واضطرابات النشاط الكهربي', 'short_desc_en' => 'Electroencephalogram for epilepsy and electrical-activity disorders'],
                    ['name_ar' => 'رسم عضلات وأعصاب (EMG/NCS)', 'name_en' => 'EMG / Nerve Conduction', 'short_desc_ar' => 'تخطيط كهربية العضلات وسرعة التوصيل العصبي', 'short_desc_en' => 'Electromyography and nerve conduction study'],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $services = $catData['services'];
            unset($catData['services']);

            $category = ServiceCategory::updateOrCreate(['slug' => $catData['slug']], $catData);

            foreach ($services as $i => $svc) {
                Service::updateOrCreate(
                    ['slug' => Str::slug($svc['name_en'])],
                    array_merge($svc, [
                        'category_id' => $category->id,
                        'module' => $catData['module'],
                        'status' => 'active',
                        'show_on_home' => true,
                        'show_on_website' => true,
                        'bookable' => true,
                        'display_order' => ($catData['display_order'] * 10) + $i,
                    ])
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ObgynServiceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'متابعة الحمل',
                'name_en' => 'Antenatal Care',
                'slug' => 'antenatal-care',
                'module' => 'obgyn',
                'display_order' => 1,
                'services' => [
                    ['name_ar' => 'أول زيارة حمل', 'name_en' => 'First Antenatal Visit', 'short_desc_ar' => 'فتح ملف الحمل وتقييم شامل وحساب موعد الولادة المتوقع', 'short_desc_en' => 'Opening the pregnancy file, full assessment, and EDD calculation'],
                    ['name_ar' => 'زيارة متابعة الحمل', 'name_en' => 'Antenatal Follow-up Visit', 'short_desc_ar' => 'متابعة دورية لصحة الأم والجنين حسب جدول منظمة الصحة العالمية', 'short_desc_en' => 'Periodic monitoring of mother and fetus per the WHO schedule'],
                    ['name_ar' => 'سونار التوليد', 'name_en' => 'Obstetric Ultrasound', 'short_desc_ar' => 'تصوير بالموجات فوق الصوتية لمتابعة نمو الجنين والمشيمة', 'short_desc_en' => 'Ultrasound imaging to track fetal growth, placenta, and wellbeing'],
                ],
            ],
            [
                'name_ar' => 'أمراض النساء',
                'name_en' => 'Gynecology',
                'slug' => 'gynecology',
                'module' => 'obgyn',
                'display_order' => 2,
                'services' => [
                    ['name_ar' => 'كشف نسائي', 'name_en' => 'Gynecological Consultation', 'short_desc_ar' => 'تشخيص وعلاج أمراض النساء واضطرابات الدورة الشهرية', 'short_desc_en' => 'Diagnosis and treatment of gynecological conditions and menstrual disorders'],
                    ['name_ar' => 'مسحة عنق الرحم', 'name_en' => 'Pap Smear Screening', 'short_desc_ar' => 'فحص الكشف المبكر عن سرطان عنق الرحم وفيروس الورم الحليمي', 'short_desc_en' => 'Early screening for cervical cancer and HPV'],
                    ['name_ar' => 'تنظيم الأسرة', 'name_en' => 'Family Planning', 'short_desc_ar' => 'استشارة ومتابعة وسائل منع الحمل المناسبة', 'short_desc_en' => 'Counseling and follow-up for suitable contraception methods'],
                ],
            ],
            [
                'name_ar' => 'الولادة',
                'name_en' => 'Delivery',
                'slug' => 'delivery',
                'module' => 'obgyn',
                'display_order' => 3,
                'services' => [
                    ['name_ar' => 'ولادة طبيعية', 'name_en' => 'Normal Delivery', 'short_desc_ar' => 'متابعة وإجراء الولادة الطبيعية مع رعاية كاملة للأم والمولود', 'short_desc_en' => 'Vaginal delivery with full care for mother and newborn'],
                    ['name_ar' => 'ولادة قيصرية', 'name_en' => 'Cesarean Section', 'short_desc_ar' => 'إجراء الولادة القيصرية الآمنة في الحالات التي تستدعيها', 'short_desc_en' => 'Safe cesarean delivery when clinically indicated'],
                ],
            ],
            [
                'name_ar' => 'الخصوبة والصحة الإنجابية',
                'name_en' => 'Fertility & Reproductive Health',
                'slug' => 'fertility-reproductive-health',
                'module' => 'obgyn',
                'display_order' => 4,
                'services' => [
                    ['name_ar' => 'استشارة تأخر الإنجاب', 'name_en' => 'Fertility Consultation', 'short_desc_ar' => 'تقييم وعلاج حالات تأخر الإنجاب لدى الزوجين', 'short_desc_en' => 'Assessment and treatment for couples facing delayed conception'],
                    ['name_ar' => 'متابعة التبويض', 'name_en' => 'Ovulation Monitoring', 'short_desc_ar' => 'متابعة التبويض بالسونار لتحديد أنسب أوقات الحمل', 'short_desc_en' => 'Ultrasound ovulation tracking to identify the fertile window'],
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
                        'module' => 'obgyn',
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

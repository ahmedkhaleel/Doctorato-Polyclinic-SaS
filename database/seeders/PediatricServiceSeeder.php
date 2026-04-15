<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PediatricServiceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'الكشف العام وصحة الطفل',
                'name_en' => 'General Checkup & Child Health',
                'slug' => 'child-health-checkup',
                'module' => 'pediatric',
                'display_order' => 1,
                'services' => [
                    ['name_ar' => 'فحص الطفل السليم', 'name_en' => 'Well-Child Visit', 'short_desc_ar' => 'فحص شامل دوري لمتابعة نمو الطفل وتطوره الجسدي والعقلي', 'short_desc_en' => 'Comprehensive periodic exam to monitor physical and mental development'],
                    ['name_ar' => 'كشف طبي عام للأطفال', 'name_en' => 'General Pediatric Consultation', 'short_desc_ar' => 'تشخيص وعلاج الأمراض الشائعة عند الأطفال مع متابعة دقيقة', 'short_desc_en' => 'Diagnosis and treatment of common childhood illnesses with careful follow-up'],
                    ['name_ar' => 'فحص حديثي الولادة', 'name_en' => 'Newborn Screening', 'short_desc_ar' => 'فحوصات مبكرة للكشف عن الأمراض الوراثية والتشوهات الخلقية', 'short_desc_en' => 'Early screening tests for genetic disorders and congenital conditions'],
                ],
            ],
            [
                'name_ar' => 'التطعيمات والتحصينات',
                'name_en' => 'Vaccinations & Immunizations',
                'slug' => 'vaccinations',
                'module' => 'pediatric',
                'display_order' => 2,
                'services' => [
                    ['name_ar' => 'التطعيمات الإجبارية', 'name_en' => 'Mandatory Vaccinations', 'short_desc_ar' => 'جميع التطعيمات الإلزامية حسب جدول وزارة الصحة مع متابعة المواعيد', 'short_desc_en' => 'All mandatory vaccinations per national schedule with appointment tracking'],
                    ['name_ar' => 'التطعيمات الإضافية', 'name_en' => 'Optional Vaccinations', 'short_desc_ar' => 'تطعيمات إضافية موصى بها مثل الروتا والجديري المائي والتهاب الكبد', 'short_desc_en' => 'Recommended extra vaccines like Rotavirus, Chickenpox, and Hepatitis'],
                    ['name_ar' => 'تطعيم الإنفلونزا الموسمية', 'name_en' => 'Seasonal Flu Vaccination', 'short_desc_ar' => 'تطعيم سنوي للوقاية من الإنفلونزا الموسمية ومضاعفاتها', 'short_desc_en' => 'Annual vaccination to prevent seasonal flu and its complications'],
                ],
            ],
            [
                'name_ar' => 'متابعة النمو والتغذية',
                'name_en' => 'Growth & Nutrition Monitoring',
                'slug' => 'growth-nutrition',
                'module' => 'pediatric',
                'display_order' => 3,
                'services' => [
                    ['name_ar' => 'متابعة منحنى النمو', 'name_en' => 'Growth Chart Monitoring', 'short_desc_ar' => 'قياس ومتابعة الطول والوزن ومحيط الرأس حسب معايير منظمة الصحة العالمية', 'short_desc_en' => 'Height, weight, and head circumference tracking per WHO growth standards'],
                    ['name_ar' => 'استشارة التغذية للأطفال', 'name_en' => 'Pediatric Nutrition Counseling', 'short_desc_ar' => 'نصائح غذائية مخصصة لكل مرحلة عمرية لضمان نمو صحي ومتوازن', 'short_desc_en' => 'Age-specific nutritional advice ensuring healthy and balanced growth'],
                    ['name_ar' => 'علاج سوء التغذية', 'name_en' => 'Malnutrition Management', 'short_desc_ar' => 'تقييم وعلاج حالات نقص الوزن أو السمنة عند الأطفال', 'short_desc_en' => 'Assessment and treatment of underweight or obesity in children'],
                ],
            ],
            [
                'name_ar' => 'أمراض الأطفال الشائعة',
                'name_en' => 'Common Childhood Diseases',
                'slug' => 'childhood-diseases',
                'module' => 'pediatric',
                'display_order' => 4,
                'services' => [
                    ['name_ar' => 'علاج أمراض الجهاز التنفسي', 'name_en' => 'Respiratory Disease Treatment', 'short_desc_ar' => 'علاج الربو والتهابات الصدر والحساسية التنفسية عند الأطفال', 'short_desc_en' => 'Treatment of asthma, chest infections, and respiratory allergies in children'],
                    ['name_ar' => 'علاج أمراض الجهاز الهضمي', 'name_en' => 'Digestive Disease Treatment', 'short_desc_ar' => 'تشخيص وعلاج المغص والإسهال والإمساك والارتجاع عند الأطفال', 'short_desc_en' => 'Diagnosis and treatment of colic, diarrhea, constipation, and reflux'],
                    ['name_ar' => 'علاج الحساسية والمناعة', 'name_en' => 'Allergy & Immunology', 'short_desc_ar' => 'تشخيص وعلاج حساسية الطعام والجلد والأمراض المناعية عند الأطفال', 'short_desc_en' => 'Diagnosis and treatment of food allergies, skin allergies, and immune disorders'],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $services = $catData['services'];
            unset($catData['services']);

            $category = ServiceCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            foreach ($services as $i => $svc) {
                Service::updateOrCreate(
                    ['slug' => Str::slug($svc['name_en'])],
                    array_merge($svc, [
                        'category_id' => $category->id,
                        'module' => 'pediatric',
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

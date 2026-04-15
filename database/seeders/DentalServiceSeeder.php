<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DentalServiceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'تنظيف وتبييض الأسنان',
                'name_en' => 'Teeth Cleaning & Whitening',
                'slug' => 'teeth-cleaning-whitening',
                'module' => 'dental',
                'display_order' => 1,
                'services' => [
                    ['name_ar' => 'تنظيف الأسنان الاحترافي', 'name_en' => 'Professional Teeth Cleaning', 'short_desc_ar' => 'إزالة الجير والبلاك بتقنية الموجات فوق الصوتية للحفاظ على صحة اللثة والأسنان', 'short_desc_en' => 'Ultrasonic plaque and tartar removal to maintain healthy gums and teeth'],
                    ['name_ar' => 'تبييض الأسنان بالليزر', 'name_en' => 'Laser Teeth Whitening', 'short_desc_ar' => 'تبييض فوري وآمن باستخدام تقنية الليزر المتقدمة للحصول على ابتسامة ناصعة', 'short_desc_en' => 'Instant and safe whitening using advanced laser technology for a bright smile'],
                    ['name_ar' => 'تبييض الأسنان المنزلي', 'name_en' => 'Home Whitening Kit', 'short_desc_ar' => 'قوالب مخصصة مع جل تبييض طبي للاستخدام المنزلي تحت إشراف الطبيب', 'short_desc_en' => 'Custom trays with medical whitening gel for supervised home use'],
                ],
            ],
            [
                'name_ar' => 'تركيبات وتجميل الأسنان',
                'name_en' => 'Dental Restorations & Cosmetics',
                'slug' => 'dental-restorations',
                'module' => 'dental',
                'display_order' => 2,
                'services' => [
                    ['name_ar' => 'فينير الأسنان', 'name_en' => 'Dental Veneers', 'short_desc_ar' => 'قشور خزفية رقيقة لتحسين مظهر الأسنان ومنحك ابتسامة هوليوود المثالية', 'short_desc_en' => 'Thin porcelain shells to enhance tooth appearance and give you a perfect Hollywood smile'],
                    ['name_ar' => 'تيجان الأسنان', 'name_en' => 'Dental Crowns', 'short_desc_ar' => 'تيجان من الزيركون أو البورسلين لحماية الأسنان التالفة واستعادة وظيفتها', 'short_desc_en' => 'Zirconia or porcelain crowns to protect damaged teeth and restore function'],
                    ['name_ar' => 'حشوات تجميلية', 'name_en' => 'Cosmetic Fillings', 'short_desc_ar' => 'حشوات بلون الأسنان الطبيعي لعلاج التسوس بشكل جمالي ومتين', 'short_desc_en' => 'Tooth-colored fillings for aesthetic and durable cavity treatment'],
                    ['name_ar' => 'جسور الأسنان', 'name_en' => 'Dental Bridges', 'short_desc_ar' => 'تعويض الأسنان المفقودة بجسور ثابتة طبيعية المظهر', 'short_desc_en' => 'Replace missing teeth with natural-looking fixed bridges'],
                ],
            ],
            [
                'name_ar' => 'زراعة الأسنان',
                'name_en' => 'Dental Implants',
                'slug' => 'dental-implants',
                'module' => 'dental',
                'display_order' => 3,
                'services' => [
                    ['name_ar' => 'زراعة أسنان فورية', 'name_en' => 'Immediate Dental Implants', 'short_desc_ar' => 'زراعة وتركيب السن في نفس الجلسة بتقنيات حديثة ومتطورة', 'short_desc_en' => 'Same-day implant and crown placement using advanced techniques'],
                    ['name_ar' => 'زراعة أسنان تقليدية', 'name_en' => 'Traditional Dental Implants', 'short_desc_ar' => 'زراعة بمرحلتين لضمان أعلى معدلات النجاح والثبات على المدى الطويل', 'short_desc_en' => 'Two-stage implants ensuring highest success rates and long-term stability'],
                    ['name_ar' => 'زراعة All-on-4', 'name_en' => 'All-on-4 Implants', 'short_desc_ar' => 'تعويض كامل للفك بأربع زرعات فقط — حل مثالي لمن فقد جميع أسنانه', 'short_desc_en' => 'Full arch restoration with only four implants — ideal for complete tooth loss'],
                ],
            ],
            [
                'name_ar' => 'تقويم الأسنان',
                'name_en' => 'Orthodontics',
                'slug' => 'orthodontics',
                'module' => 'dental',
                'display_order' => 4,
                'services' => [
                    ['name_ar' => 'تقويم شفاف (إنفيزالاين)', 'name_en' => 'Invisalign Clear Aligners', 'short_desc_ar' => 'تقويم أسنان شفاف وغير مرئي لتصحيح الاعوجاج بشكل مريح وجمالي', 'short_desc_en' => 'Invisible clear aligners for comfortable and aesthetic teeth straightening'],
                    ['name_ar' => 'تقويم أسنان معدني', 'name_en' => 'Metal Braces', 'short_desc_ar' => 'تقويم تقليدي فعال لجميع حالات سوء الإطباق مع متابعة دورية', 'short_desc_en' => 'Effective traditional braces for all malocclusion cases with regular follow-up'],
                    ['name_ar' => 'تقويم خزفي', 'name_en' => 'Ceramic Braces', 'short_desc_ar' => 'تقويم بلون الأسنان الطبيعي يجمع بين الفعالية والمظهر الجمالي', 'short_desc_en' => 'Tooth-colored braces combining effectiveness with aesthetic appeal'],
                ],
            ],
            [
                'name_ar' => 'علاج الجذور واللثة',
                'name_en' => 'Root Canal & Gum Treatment',
                'slug' => 'root-canal-gum',
                'module' => 'dental',
                'display_order' => 5,
                'services' => [
                    ['name_ar' => 'علاج عصب الأسنان', 'name_en' => 'Root Canal Treatment', 'short_desc_ar' => 'علاج جذور الأسنان الملتهبة بتقنيات دقيقة للحفاظ على السن الطبيعي', 'short_desc_en' => 'Precise root canal therapy to save natural teeth from infection'],
                    ['name_ar' => 'علاج أمراض اللثة', 'name_en' => 'Periodontal Treatment', 'short_desc_ar' => 'علاج التهابات اللثة وتراجعها بالليزر والتقنيات الحديثة', 'short_desc_en' => 'Treat gum inflammation and recession with laser and modern techniques'],
                    ['name_ar' => 'جراحة الفم', 'name_en' => 'Oral Surgery', 'short_desc_ar' => 'خلع أسنان العقل وجراحات الفم المتقدمة بأعلى معايير الأمان', 'short_desc_en' => 'Wisdom tooth extraction and advanced oral surgeries with highest safety standards'],
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
                        'module' => 'dental',
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

<?php

namespace Database\Seeders;

use App\Models\PackageBundle;
use App\Models\PackageBundleService;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DentalPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name_ar' => 'باقة الابتسامة المثالية',
                'name_en' => 'Perfect Smile Package',
                'description_ar' => 'باقة شاملة للحصول على ابتسامة مثالية تشمل التنظيف والتبييض بالليزر وفحص شامل للأسنان',
                'description_en' => 'Complete package for a perfect smile including cleaning, laser whitening, and comprehensive dental exam',
                'total_price' => 2500,
                'original_price' => 3500,
                'module' => 'dental',
                'display_order' => 1,
                'service_slugs' => ['professional-teeth-cleaning', 'laser-teeth-whitening'],
            ],
            [
                'name_ar' => 'باقة زراعة الأسنان المتكاملة',
                'name_en' => 'Complete Implant Package',
                'description_ar' => 'باقة زراعة أسنان شاملة تتضمن الزراعة والتاج والمتابعة مع أفضل أطباء الزراعة',
                'description_en' => 'Comprehensive implant package including implant, crown, and follow-up with top implant specialists',
                'total_price' => 12000,
                'original_price' => 16000,
                'module' => 'dental',
                'display_order' => 2,
                'service_slugs' => ['immediate-dental-implants', 'dental-crowns'],
            ],
            [
                'name_ar' => 'باقة هوليوود سمايل',
                'name_en' => 'Hollywood Smile Package',
                'description_ar' => 'احصل على ابتسامة هوليوود المثالية مع فينير الأسنان وتبييض وتنظيف احترافي',
                'description_en' => 'Get the perfect Hollywood smile with dental veneers, whitening, and professional cleaning',
                'total_price' => 18000,
                'original_price' => 25000,
                'module' => 'dental',
                'display_order' => 3,
                'service_slugs' => ['dental-veneers', 'laser-teeth-whitening', 'professional-teeth-cleaning'],
            ],
        ];

        foreach ($packages as $data) {
            $slugs = $data['service_slugs'];
            unset($data['service_slugs']);

            $bundle = PackageBundle::updateOrCreate(
                ['name_en' => $data['name_en']],
                array_merge($data, ['is_active' => true])
            );

            // Link services
            $bundle->services()->delete();
            foreach ($slugs as $i => $slug) {
                $service = Service::where('slug', $slug)->first();
                if ($service) {
                    PackageBundleService::create([
                        'package_bundle_id' => $bundle->id,
                        'service_id' => $service->id,
                        'sessions_count' => 1, 'bundle_price' => 0,
                        'display_order' => $i,
                    ]);
                }
            }
        }
    }
}

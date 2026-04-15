<?php

namespace Database\Seeders;

use App\Models\PackageBundle;
use App\Models\PackageBundleService;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PediatricPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name_ar' => 'باقة الطفل السليم',
                'name_en' => 'Well-Child Package',
                'description_ar' => 'باقة متابعة شاملة تشمل فحص الطفل السليم ومتابعة النمو واستشارة التغذية لضمان نمو صحي متوازن',
                'description_en' => 'Comprehensive follow-up package including well-child exam, growth monitoring, and nutrition counseling',
                'total_price' => 1200,
                'original_price' => 1800,
                'module' => 'pediatric',
                'display_order' => 1,
                'service_slugs' => ['well-child-visit', 'growth-chart-monitoring', 'pediatric-nutrition-counseling'],
            ],
            [
                'name_ar' => 'باقة التطعيمات الشاملة',
                'name_en' => 'Complete Vaccination Package',
                'description_ar' => 'باقة تطعيمات شاملة تتضمن التطعيمات الإجبارية والإضافية الموصى بها مع متابعة الجدول الزمني',
                'description_en' => 'Complete vaccination package including mandatory and recommended vaccines with schedule tracking',
                'total_price' => 3000,
                'original_price' => 4200,
                'module' => 'pediatric',
                'display_order' => 2,
                'service_slugs' => ['mandatory-vaccinations', 'optional-vaccinations', 'seasonal-flu-vaccination'],
            ],
            [
                'name_ar' => 'باقة صحة الطفل المتكاملة',
                'name_en' => 'Complete Child Health Package',
                'description_ar' => 'باقة رعاية متكاملة تشمل الفحص العام والتطعيمات ومتابعة النمو وفحص الحساسية',
                'description_en' => 'Integrated care package with general checkup, vaccinations, growth tracking, and allergy screening',
                'total_price' => 4500,
                'original_price' => 6500,
                'module' => 'pediatric',
                'display_order' => 3,
                'service_slugs' => ['general-pediatric-consultation', 'mandatory-vaccinations', 'growth-chart-monitoring', 'allergy-immunology'],
            ],
        ];

        foreach ($packages as $data) {
            $slugs = $data['service_slugs'];
            unset($data['service_slugs']);

            $bundle = PackageBundle::updateOrCreate(
                ['name_en' => $data['name_en']],
                array_merge($data, ['is_active' => true])
            );

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

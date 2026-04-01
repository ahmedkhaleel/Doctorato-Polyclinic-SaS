<?php

namespace Database\Seeders;

use App\Models\PackageBundle;
use App\Models\PackageBundleService;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PackageBundleSeeder extends Seeder
{
    /**
     * Seed package bundles with associated services.
     */
    public function run(): void
    {
        $services = Service::all();

        if ($services->isEmpty()) {
            $this->command->warn('No services found. Skipping PackageBundleSeeder.');
            return;
        }

        // Helper to find a service by partial English name match
        $findService = function (string $partial) use ($services) {
            return $services->first(fn ($s) => str_contains(strtolower($s->name_en), strtolower($partial)));
        };

        // ── 1. Bride Package ───────────────────────────────────────────
        $brideBundle = PackageBundle::updateOrCreate(
            ['name_en' => 'Bride Package'],
            [
                'name_ar'        => 'باقة العروس',
                'name_en'        => 'Bride Package',
                'description_ar' => 'باقة متكاملة لتحضير العروس تشمل العناية بالبشرة وازالة الشعر والنضارة. استعدي ليومك المميز بأفضل النتائج وأسعار حصرية.',
                'description_en' => 'A comprehensive bridal preparation package including skincare, hair removal, and radiance treatments. Get ready for your special day with the best results at exclusive prices.',
                'original_price' => 8000.00,
                'total_price'    => 5500.00,
                'is_active'      => true,
                'image'          => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800&q=80',
                'display_order'  => 1,
            ]
        );

        $brideServices = [
            ['partial' => 'HydraFacial',         'sessions' => 4, 'discount' => 30, 'price' => 1600],
            ['partial' => 'Full Body Laser',      'sessions' => 3, 'discount' => 35, 'price' => 1950],
            ['partial' => 'Pigmentation',         'sessions' => 3, 'discount' => 25, 'price' => 1200],
            ['partial' => 'Glow Injections',      'sessions' => 2, 'discount' => 25, 'price' => 750],
        ];

        $this->attachServices($brideBundle, $brideServices, $findService);

        // ── 2. Skin Rejuvenation Package ───────────────────────────────
        $skinBundle = PackageBundle::updateOrCreate(
            ['name_en' => 'Skin Rejuvenation Package'],
            [
                'name_ar'        => 'باقة تجديد البشرة',
                'name_en'        => 'Skin Rejuvenation Package',
                'description_ar' => 'برنامج متكامل لتجديد البشرة واستعادة نضارتها وحيويتها. يشمل جلسات الهيدرافيشل والتقشير والبلازما للحصول على بشرة مشرقة وشابة.',
                'description_en' => 'A complete skin renewal program to restore radiance and vitality. Includes HydraFacial, peeling, and plasma sessions for bright, youthful skin.',
                'original_price' => 4500.00,
                'total_price'    => 3200.00,
                'is_active'      => true,
                'image'          => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',
                'display_order'  => 2,
            ]
        );

        $skinServices = [
            ['partial' => 'HydraFacial',       'sessions' => 3, 'discount' => 30, 'price' => 1200],
            ['partial' => 'Chemical & Cold',   'sessions' => 3, 'discount' => 25, 'price' => 1050],
            ['partial' => 'PRP',               'sessions' => 2, 'discount' => 30, 'price' => 950],
        ];

        $this->attachServices($skinBundle, $skinServices, $findService);

        // ── 3. Laser Hair Removal Package ──────────────────────────────
        $laserBundle = PackageBundle::updateOrCreate(
            ['name_en' => 'Complete Laser Package'],
            [
                'name_ar'        => 'باقة الليزر المتكاملة',
                'name_en'        => 'Complete Laser Package',
                'description_ar' => 'باقة ازالة الشعر بالليزر لكامل الجسم باستخدام احدث الاجهزة المعتمدة عالميا. تشمل الباقة جلسات متعددة مع متابعة طبية شاملة ونتائج مضمونة.',
                'description_en' => 'Full body laser hair removal package using the latest internationally certified devices. Includes multiple sessions with comprehensive medical follow-up and guaranteed results.',
                'original_price' => 5000.00,
                'total_price'    => 3800.00,
                'is_active'      => true,
                'image'          => 'https://images.unsplash.com/photo-1598524374912-6b0b0bab3da4?w=800&q=80',
                'display_order'  => 3,
            ]
        );

        $laserServices = [
            ['partial' => 'Full Body Laser',  'sessions' => 6, 'discount' => 25, 'price' => 2400],
            ['partial' => 'Acne',              'sessions' => 3, 'discount' => 30, 'price' => 1400],
        ];

        $this->attachServices($laserBundle, $laserServices, $findService);
    }

    /**
     * Attach services to a bundle, matching by partial name.
     */
    private function attachServices(PackageBundle $bundle, array $serviceConfigs, callable $findService): void
    {
        // Remove old services for this bundle (for re-seeding)
        $bundle->services()->delete();

        $order = 1;
        foreach ($serviceConfigs as $config) {
            $service = $findService($config['partial']);
            if (!$service) {
                continue;
            }

            PackageBundleService::create([
                'package_bundle_id'   => $bundle->id,
                'service_id'          => $service->id,
                'sessions_count'      => $config['sessions'],
                'discount_percentage' => $config['discount'],
                'bundle_price'        => $config['price'],
                'display_order'       => $order++,
            ]);
        }
    }
}

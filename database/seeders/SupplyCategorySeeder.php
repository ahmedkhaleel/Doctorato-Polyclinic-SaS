<?php

namespace Database\Seeders;

use App\Models\SupplyCategory;
use Illuminate\Database\Seeder;

class SupplyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_en' => 'Injectables',
                'name_ar' => 'حقن تجميلية',
                'icon' => 'syringe',
                'color' => '#8B5CF6',
                'description' => 'Botox, Fillers, Skin Boosters, PRP',
                'display_order' => 1,
            ],
            [
                'name_en' => 'Laser Supplies',
                'name_ar' => 'مستلزمات ليزر',
                'icon' => 'zap',
                'color' => '#F59E0B',
                'description' => 'Laser tips, cartridges, cooling gels, protective eyewear',
                'display_order' => 2,
            ],
            [
                'name_en' => 'Skincare Products',
                'name_ar' => 'منتجات عناية بالبشرة',
                'icon' => 'droplet',
                'color' => '#EC4899',
                'description' => 'Cleansers, moisturizers, serums, sunscreens',
                'display_order' => 3,
            ],
            [
                'name_en' => 'Consumables',
                'name_ar' => 'مستهلكات',
                'icon' => 'layers',
                'color' => '#6B7280',
                'description' => 'Gloves, masks, cotton, gauze, bed covers',
                'display_order' => 4,
            ],
            [
                'name_en' => 'Peeling Agents',
                'name_ar' => 'مواد تقشير',
                'icon' => 'sparkles',
                'color' => '#10B981',
                'description' => 'Chemical peels, TCA, glycolic acid, salicylic acid',
                'display_order' => 5,
            ],
            [
                'name_en' => 'Medical Devices',
                'name_ar' => 'أجهزة طبية',
                'icon' => 'cpu',
                'color' => '#3B82F6',
                'description' => 'Dermapen tips, microneedling devices, LED masks',
                'display_order' => 6,
            ],
            [
                'name_en' => 'Anesthetics',
                'name_ar' => 'مواد تخدير',
                'icon' => 'shield',
                'color' => '#EF4444',
                'description' => 'Topical anesthetics, numbing creams, lidocaine',
                'display_order' => 7,
            ],
            [
                'name_en' => 'Needles & Syringes',
                'name_ar' => 'إبر ومحاقن',
                'icon' => 'target',
                'color' => '#14B8A6',
                'description' => 'Mesotherapy needles, cannulas, syringes',
                'display_order' => 8,
            ],
        ];

        foreach ($categories as $cat) {
            SupplyCategory::updateOrCreate(
                ['name_en' => $cat['name_en']],
                $cat
            );
        }
    }
}

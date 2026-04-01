<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Seed blog post tags (bilingual).
     */
    public function run(): void
    {
        $tags = [
            [
                'name_ar' => 'العناية بالبشرة',
                'name_en' => 'Skincare',
                'slug'    => 'skincare',
            ],
            [
                'name_ar' => 'الليزر',
                'name_en' => 'Laser',
                'slug'    => 'laser',
            ],
            [
                'name_ar' => 'حب الشباب',
                'name_en' => 'Acne',
                'slug'    => 'acne',
            ],
            [
                'name_ar' => 'مكافحة الشيخوخة',
                'name_en' => 'Anti-Aging',
                'slug'    => 'anti-aging',
            ],
            [
                'name_ar' => 'إزالة الشعر',
                'name_en' => 'Hair Removal',
                'slug'    => 'hair-removal',
            ],
            [
                'name_ar' => 'البوتكس',
                'name_en' => 'Botox',
                'slug'    => 'botox',
            ],
            [
                'name_ar' => 'الفيلر',
                'name_en' => 'Fillers',
                'slug'    => 'fillers',
            ],
            [
                'name_ar' => 'تفتيح البشرة',
                'name_en' => 'Skin Whitening',
                'slug'    => 'skin-whitening',
            ],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }
    }
}

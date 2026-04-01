<?php

namespace Database\Seeders;

use App\Models\Gallery;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    /**
     * Seed gallery items across different categories.
     */
    public function run(): void
    {
        $items = [
            // ── Clinic Photos ─────────────────────────────────────────────
            [
                'category'        => 'clinic',
                'image_path'      => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'استقبال عيادة أورا ديرما كلينك',
                'caption_en'      => 'Aura Derma Clinic Reception',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 1,
                'is_visible'      => true,
            ],
            [
                'category'        => 'clinic',
                'image_path'      => 'https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'غرفة الاستشارات والفحص الطبي',
                'caption_en'      => 'Consultation & Examination Room',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 2,
                'is_visible'      => true,
            ],
            [
                'category'        => 'clinic',
                'image_path'      => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'غرفة العلاج والإجراءات التجميلية',
                'caption_en'      => 'Treatment & Cosmetic Procedures Room',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 3,
                'is_visible'      => true,
            ],
            [
                'category'        => 'clinic',
                'image_path'      => 'https://images.unsplash.com/photo-1666214280557-091e129d66e4?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'ممر العيادة الأنيق',
                'caption_en'      => 'Elegant Clinic Hallway',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 4,
                'is_visible'      => true,
            ],
            [
                'category'        => 'clinic',
                'image_path'      => 'https://images.unsplash.com/photo-1638202993928-7267aad84c31?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'منطقة الانتظار المريحة',
                'caption_en'      => 'Comfortable Waiting Area',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 5,
                'is_visible'      => true,
            ],

            // ── Equipment ─────────────────────────────────────────────────
            [
                'category'        => 'equipment',
                'image_path'      => 'https://images.unsplash.com/photo-1609840114035-3c981b782dfe?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'جهاز الليزر المتطور لإزالة الشعر',
                'caption_en'      => 'Advanced Laser Hair Removal Device',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 6,
                'is_visible'      => true,
            ],
            [
                'category'        => 'equipment',
                'image_path'      => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'جهاز الهيدرافيشل للعناية بالبشرة',
                'caption_en'      => 'HydraFacial Skincare Device',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 7,
                'is_visible'      => true,
            ],
            [
                'category'        => 'equipment',
                'image_path'      => 'https://images.unsplash.com/photo-1551190822-a9ce113ac100?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'أجهزة التشخيص المتقدمة',
                'caption_en'      => 'Advanced Diagnostic Equipment',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 8,
                'is_visible'      => true,
            ],
            [
                'category'        => 'equipment',
                'image_path'      => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'جهاز الفراكشنال ليزر',
                'caption_en'      => 'Fractional Laser Device',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 9,
                'is_visible'      => true,
            ],

            // ── Before & After ────────────────────────────────────────────
            [
                'category'        => 'before-after',
                'image_path'      => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'نتائج علاج حب الشباب - قبل وبعد',
                'caption_en'      => 'Acne Treatment Results - Before & After',
                'is_before_after' => true,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 10,
                'is_visible'      => true,
            ],
            [
                'category'        => 'before-after',
                'image_path'      => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'نتائج جلسات تفتيح البشرة - قبل وبعد',
                'caption_en'      => 'Skin Whitening Results - Before & After',
                'is_before_after' => true,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 11,
                'is_visible'      => true,
            ],
            [
                'category'        => 'before-after',
                'image_path'      => 'https://images.unsplash.com/photo-1512290923902-8a9f81dc236c?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'نتائج علاج التصبغات - قبل وبعد',
                'caption_en'      => 'Pigmentation Treatment Results - Before & After',
                'is_before_after' => true,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 12,
                'is_visible'      => true,
            ],

            // ── Team ──────────────────────────────────────────────────────
            [
                'category'        => 'team',
                'image_path'      => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'فريق عيادة أورا ديرما كلينك الطبي',
                'caption_en'      => 'Aura Derma Clinic Medical Team',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 13,
                'is_visible'      => true,
            ],
            [
                'category'        => 'team',
                'image_path'      => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'طبيبة الجلدية المتخصصة',
                'caption_en'      => 'Specialized Dermatologist',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 14,
                'is_visible'      => true,
            ],
            [
                'category'        => 'team',
                'image_path'      => 'https://images.unsplash.com/photo-1594824476967-48c8b964ac31?w=800&q=80',
                'video_url'       => null,
                'caption_ar'      => 'فريق التجميل والعناية بالبشرة',
                'caption_en'      => 'Aesthetics & Skincare Team',
                'is_before_after' => false,
                'before_image'    => null,
                'after_image'     => null,
                'display_order'   => 15,
                'is_visible'      => true,
            ],
        ];

        foreach ($items as $item) {
            Gallery::updateOrCreate(
                ['caption_en' => $item['caption_en']],
                $item
            );
        }
    }
}

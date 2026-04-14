<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    /**
     * Seed hero slides for the website homepage.
     */
    public function run(): void
    {
        $slides = [
            [
                'title_ar'       => 'عيادة دكتوراتو كلينك',
                'title_en'       => 'Doctorato Polyclinic',
                'subtitle_ar'    => 'للأمراض الجلدية والتجميل والليزر',
                'subtitle_en'    => 'Dermatology, Aesthetics & Laser',
                'description_ar' => 'نقدم أحدث تقنيات العناية بالبشرة والتجميل مع نخبة من أمهر الأطباء المتخصصين',
                'description_en' => 'We offer the latest skincare and cosmetic technologies with an elite team of specialist doctors',
                'button_text_ar' => 'احجز موعدك الآن',
                'button_text_en' => 'Book Your Appointment',
                'button_link'    => '/booking',
                'image'          => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=1920&q=80',
                'display_order'  => 1,
                'is_active'      => true,
            ],
            [
                'title_ar'       => 'خبرة تفوق ١٠ سنوات',
                'title_en'       => 'Over 10 Years of Experience',
                'subtitle_ar'    => 'في الجلدية والتجميل والليزر',
                'subtitle_en'    => 'In Dermatology, Aesthetics & Laser',
                'description_ar' => 'فريق طبي متكامل بقيادة د. أسماء حمدي الحاصلة على دكتوراه وزمالة الأمراض الجلدية',
                'description_en' => 'A complete medical team led by Dr. Asmaa Hamdy with a Doctorate and Fellowship in Dermatology',
                'button_text_ar' => 'تعرف على فريقنا',
                'button_text_en' => 'Meet Our Team',
                'button_link'    => '/doctors',
                'image'          => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=1920&q=80',
                'display_order'  => 2,
                'is_active'      => true,
            ],
            [
                'title_ar'       => 'أحدث الأجهزة العالمية',
                'title_en'       => 'Latest International Equipment',
                'subtitle_ar'    => 'تقنيات متطورة لنتائج مثالية',
                'subtitle_en'    => 'Advanced Technologies for Perfect Results',
                'description_ar' => 'نستخدم أحدث أجهزة الليزر والتجميل المعتمدة عالمياً لضمان أفضل النتائج',
                'description_en' => 'We use the latest internationally certified laser and cosmetic devices for the best results',
                'button_text_ar' => 'استكشف خدماتنا',
                'button_text_en' => 'Explore Our Services',
                'button_link'    => '/services',
                'image'          => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1920&q=80',
                'display_order'  => 3,
                'is_active'      => true,
            ],
        ];

        foreach ($slides as $slide) {
            HeroSlide::updateOrCreate(
                ['display_order' => $slide['display_order']],
                $slide
            );
        }
    }
}

<?php

namespace App\Services;

use App\Models\SeoPage;

class SeoService
{
    /**
     * Get SEO data for a page by its identifier.
     */
    public static function get(string $pageIdentifier): array
    {
        $seoPage = SeoPage::where('page_identifier', $pageIdentifier)->first();

        if (!$seoPage) {
            return self::defaults($pageIdentifier);
        }

        return [
            'title_ar' => $seoPage->title_ar ?? '',
            'title_en' => $seoPage->title_en ?? '',
            'description_ar' => $seoPage->description_ar ?? '',
            'description_en' => $seoPage->description_en ?? '',
            'keywords' => $seoPage->keywords ?? '',
            'og_image' => $seoPage->og_image ?? '',
            'canonical_url' => $seoPage->canonical_url ?? '',
            'is_indexable' => $seoPage->is_indexable ?? true,
            'structured_data' => $seoPage->structured_data ?? null,
        ];
    }

    /**
     * Fallback SEO defaults if no database record exists.
     */
    private static function defaults(string $pageIdentifier): array
    {
        $defaults = [
            'home' => [
                'title_ar' => 'عيادة دكتوراتو للجلدية والتجميل',
                'title_en' => 'AURA Derma Aesthetic Clinic',
                'description_ar' => 'عيادة دكتوراتو للجلدية والتجميل - أحدث تقنيات العناية بالبشرة والتجميل في مصر.',
                'description_en' => 'AURA Derma Aesthetic Clinic - The latest skincare and cosmetic technologies in Egypt.',
            ],
            'about' => [
                'title_ar' => 'عن العيادة - عيادة دكتوراتو',
                'title_en' => 'About Us - AURA Derma Clinic',
                'description_ar' => 'تعرف على عيادة دكتوراتو للجلدية والتجميل.',
                'description_en' => 'Learn about AURA Derma Aesthetic Clinic.',
            ],
        ];

        $base = $defaults[$pageIdentifier] ?? [
            'title_ar' => 'عيادة دكتوراتو',
            'title_en' => 'AURA Derma Clinic',
            'description_ar' => '',
            'description_en' => '',
        ];

        return array_merge($base, [
            'keywords' => '',
            'og_image' => '',
            'canonical_url' => '',
            'is_indexable' => true,
            'structured_data' => null,
        ]);
    }
}

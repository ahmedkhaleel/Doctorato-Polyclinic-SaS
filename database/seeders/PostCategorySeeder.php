<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use Illuminate\Database\Seeder;

class PostCategorySeeder extends Seeder
{
    /**
     * Seed blog post categories.
     */
    public function run(): void
    {
        $categories = [
            [
                'name_ar'         => 'نصائح العناية بالبشرة',
                'name_en'         => 'Skincare Tips',
                'slug'            => 'skincare-tips',
                'description_ar'  => 'مقالات ونصائح متخصصة للعناية بالبشرة والحفاظ على صحتها ونضارتها.',
                'description_en'  => 'Specialized articles and tips for skincare, maintaining skin health and radiance.',
            ],
            [
                'name_ar'         => 'أحدث العلاجات والتقنيات',
                'name_en'         => 'Latest Treatments',
                'slug'            => 'latest-treatments',
                'description_ar'  => 'تعرف على أحدث العلاجات والتقنيات في عالم الجلدية والتجميل.',
                'description_en'  => 'Discover the latest treatments and technologies in the world of dermatology and aesthetics.',
            ],
            [
                'name_ar'         => 'توعية صحية',
                'name_en'         => 'Health Awareness',
                'slug'            => 'health-awareness',
                'description_ar'  => 'مقالات توعوية عن الأمراض الجلدية وطرق الوقاية والعلاج.',
                'description_en'  => 'Awareness articles about skin diseases, prevention methods, and treatments.',
            ],
            [
                'name_ar'         => 'أسئلة وأجوبة',
                'name_en'         => 'Q&A',
                'slug'            => 'qa',
                'description_ar'  => 'إجابات على الأسئلة الأكثر شيوعاً حول خدماتنا وعلاجات البشرة.',
                'description_en'  => 'Answers to the most common questions about our services and skin treatments.',
            ],
        ];

        foreach ($categories as $category) {
            PostCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}

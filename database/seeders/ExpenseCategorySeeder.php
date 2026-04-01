<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'الإيجار والمرافق',
                'name_en' => 'Rent & Utilities',
                'sort_order' => 1,
                'items' => [
                    ['name_ar' => 'إيجار العيادة', 'name_en' => 'Clinic Rent'],
                    ['name_ar' => 'كهرباء', 'name_en' => 'Electricity'],
                    ['name_ar' => 'مياه', 'name_en' => 'Water'],
                    ['name_ar' => 'إنترنت', 'name_en' => 'Internet'],
                    ['name_ar' => 'هاتف', 'name_en' => 'Phone'],
                ],
            ],
            [
                'name_ar' => 'الرواتب',
                'name_en' => 'Salaries',
                'sort_order' => 2,
                'items' => [
                    ['name_ar' => 'رواتب الأطباء', 'name_en' => 'Doctor Salaries'],
                    ['name_ar' => 'رواتب الممرضات', 'name_en' => 'Nurse Salaries'],
                    ['name_ar' => 'رواتب الاستقبال', 'name_en' => 'Receptionist Salaries'],
                    ['name_ar' => 'رواتب أخرى', 'name_en' => 'Other Salaries'],
                ],
            ],
            [
                'name_ar' => 'المستلزمات الطبية',
                'name_en' => 'Medical Supplies',
                'sort_order' => 3,
                'items' => [
                    ['name_ar' => 'مستلزمات ليزر', 'name_en' => 'Laser Supplies'],
                    ['name_ar' => 'مستلزمات تجميل', 'name_en' => 'Cosmetic Supplies'],
                    ['name_ar' => 'قفازات ومعقمات', 'name_en' => 'Gloves & Sanitizers'],
                    ['name_ar' => 'أدوية ومراهم', 'name_en' => 'Medications & Ointments'],
                ],
            ],
            [
                'name_ar' => 'صيانة المعدات',
                'name_en' => 'Equipment Maintenance',
                'sort_order' => 4,
                'items' => [
                    ['name_ar' => 'صيانة أجهزة الليزر', 'name_en' => 'Laser Device Maintenance'],
                    ['name_ar' => 'صيانة عامة', 'name_en' => 'General Maintenance'],
                    ['name_ar' => 'قطع غيار', 'name_en' => 'Spare Parts'],
                ],
            ],
            [
                'name_ar' => 'التسويق والإعلان',
                'name_en' => 'Marketing & Advertising',
                'sort_order' => 5,
                'items' => [
                    ['name_ar' => 'إعلانات سوشيال ميديا', 'name_en' => 'Social Media Ads'],
                    ['name_ar' => 'طباعة ومواد دعائية', 'name_en' => 'Print Materials'],
                    ['name_ar' => 'تصوير وفيديو', 'name_en' => 'Photography & Video'],
                ],
            ],
            [
                'name_ar' => 'مصاريف إدارية',
                'name_en' => 'Administrative',
                'sort_order' => 6,
                'items' => [
                    ['name_ar' => 'قرطاسية', 'name_en' => 'Stationery'],
                    ['name_ar' => 'برمجيات واشتراكات', 'name_en' => 'Software & Subscriptions'],
                    ['name_ar' => 'نقل ومواصلات', 'name_en' => 'Transportation'],
                ],
            ],
            [
                'name_ar' => 'مصاريف أخرى',
                'name_en' => 'Other Expenses',
                'sort_order' => 7,
                'items' => [
                    ['name_ar' => 'ضيافة', 'name_en' => 'Hospitality'],
                    ['name_ar' => 'تأمين', 'name_en' => 'Insurance'],
                    ['name_ar' => 'مصاريف متنوعة', 'name_en' => 'Miscellaneous'],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $items = $catData['items'];
            unset($catData['items']);

            $category = ExpenseCategory::updateOrCreate(
                ['name_en' => $catData['name_en']],
                array_merge($catData, ['is_active' => true])
            );

            foreach ($items as $item) {
                ExpenseItem::updateOrCreate(
                    ['name_en' => $item['name_en'], 'expense_category_id' => $category->id],
                    array_merge($item, ['is_active' => true])
                );
            }
        }
    }
}

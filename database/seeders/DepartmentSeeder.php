<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name_ar' => 'الإدارة',         'name_en' => 'Administration',  'display_order' => 1],
            ['name_ar' => 'الاستقبال',       'name_en' => 'Reception',       'display_order' => 2],
            ['name_ar' => 'القسم الطبي',     'name_en' => 'Medical',         'display_order' => 3],
            ['name_ar' => 'التمريض',         'name_en' => 'Nursing',         'display_order' => 4],
            ['name_ar' => 'المحاسبة',        'name_en' => 'Accounting',      'display_order' => 5],
            ['name_ar' => 'التسويق',         'name_en' => 'Marketing',       'display_order' => 6],
            ['name_ar' => 'تقنية المعلومات', 'name_en' => 'IT',              'display_order' => 7],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['name_en' => $dept['name_en']],
                $dept
            );
        }
    }
}

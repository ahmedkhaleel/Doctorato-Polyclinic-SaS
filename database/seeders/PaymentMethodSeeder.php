<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name_ar' => 'نقدي', 'name_en' => 'Cash', 'sort_order' => 1],
            ['name_ar' => 'فيزا', 'name_en' => 'Visa', 'sort_order' => 2],
            ['name_ar' => 'إنستاباي', 'name_en' => 'Instapay', 'sort_order' => 3],
            ['name_ar' => 'تحويل بنكي', 'name_en' => 'Bank Transfer', 'sort_order' => 4],
            ['name_ar' => 'محفظة إلكترونية', 'name_en' => 'E-Wallet', 'sort_order' => 5],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['name_en' => $method['name_en']],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

/**
 * Demo doctors for the psychiatry & neurology modules so enabling either
 * module shows a bookable specialist instead of an empty booking page.
 * Idempotent (firstOrCreate by name_en).
 */
class NeuropsychDemoSeeder extends Seeder
{
    public function run(): void
    {
        Doctor::firstOrCreate(
            ['name_en' => 'Dr. Omar Fathy'],
            [
                'name_ar' => 'د. عمر فتحي',
                'specialization_ar' => 'الطب النفسي',
                'specialization_en' => 'Psychiatry',
                'doctor_type' => 'consultant',
                'status' => 'active',
                'module' => 'psychiatry',
                'psychiatry_consultation_fee' => 300,
            ]
        );

        Doctor::firstOrCreate(
            ['name_en' => 'Dr. Layla Mansour'],
            [
                'name_ar' => 'د. ليلى منصور',
                'specialization_ar' => 'طب الأعصاب',
                'specialization_en' => 'Neurology',
                'doctor_type' => 'consultant',
                'status' => 'active',
                'module' => 'neurology',
                'neurology_consultation_fee' => 300,
            ]
        );
    }
}

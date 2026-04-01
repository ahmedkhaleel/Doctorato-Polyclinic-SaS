<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorServiceRate;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctorRole = Role::where('name', 'doctor')->first();

        // ── Service Rate Definitions ───────────────────────────
        // Cosmetic / Injection services (Specialist 20%, Consultant 30%)
        $cosmeticServices = [
            'Neauvia Filler', 'Celesome Filler', 'Evanthia Filler', 'Mifill Filler',
            'Xeomin Botox', 'Allergan Botox (LE)', 'Gentox Botox',
            'Profhilo', 'RRS Long Lasting', 'RRS HA Eyes', 'Rich',
            'Radiesse', 'Olidia', 'Sculptra', 'Yakoot',
            'Dermapen without Plasma', 'Dermapen with Plasma',
            'Dermapen with Plasma & Mesotherapy', 'Dermapen with Plasma & Subcision',
            'Dermapen with LC without Plasma',
            'Green Peel', 'Hydrafacial Level 1', 'Hydrafacial Level 2', 'Hydrafacial Level 3',
        ];

        // Laser services (Specialist 20%, Consultant 25%)
        $laserServices = [
            'Laser - Underarm', 'Laser - Mustache', 'Laser - Mustache + Shin',
            'Laser - Bikini', 'Laser - Face', 'Laser - Bikini + Underarms',
            'Laser - Upper Half Arm', 'Laser - Lower Half Arm',
            'Laser - Upper Half Leg', 'Laser - Lower Half Leg',
            'Laser - Full Arm', 'Laser - Full Leg',
            'Laser - Belly', 'Laser - Back',
            'Laser - Full Body (w/o Back & Belly)',
        ];

        // Resolve service IDs
        $cosmeticServiceIds = Service::whereIn('name_en', $cosmeticServices)->pluck('id')->toArray();
        $laserServiceIds    = Service::whereIn('name_en', $laserServices)->pluck('id')->toArray();

        // ── DR Alaa Elawady ──────────────────────────────────
        $userAlaa = User::firstOrCreate(
            ['email' => 'dr.alaa@auraderma.com'],
            [
                'name'      => 'DR Alaa Elawady',
                'password'  => Hash::make('password'),
                'role_id'   => $doctorRole->id,
                'is_active' => true,
            ]
        );

        $doctorAlaa = Doctor::updateOrCreate(
            ['name_en' => 'DR Alaa Elawady'],
            [
                'name_ar'                      => 'د. آلاء العوضي',
                'specialization_ar'            => 'أخصائى جلدية وتناسلية',
                'specialization_en'            => 'Dermatology & Venereology Specialist',
                'doctor_type'                  => 'specialist',
                'status'                       => 'active',
                'display_order'                => 1,
                'default_commission_percentage' => 20.00,
                'dermatology_commission'        => 40.00,
                'cosmetic_commission'           => 40.00,
                'followup_commission'           => 40.00,
                'user_id'                      => $userAlaa->id,
            ]
        );

        // Weekly schedule: Saturday 6:00 PM – 9:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorAlaa->id, 'day_of_week' => 0], // 0 = Saturday
            [
                'start_time' => '18:00:00',
                'end_time'   => '21:00:00',
                'is_active'  => true,
            ]
        );

        // Service Rates (Specialist: 20% cosmetic, 20% laser)
        $this->seedServiceRates($doctorAlaa->id, $cosmeticServiceIds, 20.00);
        $this->seedServiceRates($doctorAlaa->id, $laserServiceIds, 20.00);

        // ── Dr Eman Magdy ──────────────────────────────────
        $userEman = User::firstOrCreate(
            ['email' => 'dr.eman@auraderma.com'],
            [
                'name'      => 'Dr Eman Magdy',
                'password'  => Hash::make('password'),
                'role_id'   => $doctorRole->id,
                'is_active' => true,
            ]
        );

        $doctorEman = Doctor::updateOrCreate(
            ['name_en' => 'Dr Eman Magdy'],
            [
                'name_ar'                      => 'د. إيمان مجدي',
                'specialization_ar'            => 'أخصائى جلدية وتناسلية',
                'specialization_en'            => 'Dermatology & Venereology Specialist',
                'doctor_type'                  => 'specialist',
                'status'                       => 'active',
                'display_order'                => 2,
                'default_commission_percentage' => 20.00,
                'dermatology_commission'        => 40.00,
                'cosmetic_commission'           => 40.00,
                'followup_commission'           => 40.00,
                'user_id'                      => $userEman->id,
            ]
        );

        // Weekly schedule: Tuesday 6:00 PM – 9:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorEman->id, 'day_of_week' => 3], // 3 = Tuesday
            [
                'start_time' => '18:00:00',
                'end_time'   => '21:00:00',
                'is_active'  => true,
            ]
        );

        // Weekly schedule: Wednesday 6:00 PM – 9:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorEman->id, 'day_of_week' => 4], // 4 = Wednesday
            [
                'start_time' => '18:00:00',
                'end_time'   => '21:00:00',
                'is_active'  => true,
            ]
        );

        // Service Rates (Specialist: 20% cosmetic, 20% laser)
        $this->seedServiceRates($doctorEman->id, $cosmeticServiceIds, 20.00);
        $this->seedServiceRates($doctorEman->id, $laserServiceIds, 20.00);

        // ── Dr Amira Ahmed ──────────────────────────────────
        $userAmira = User::firstOrCreate(
            ['email' => 'dr.amira@auraderma.com'],
            [
                'name'      => 'Dr Amira Ahmed',
                'password'  => Hash::make('password'),
                'role_id'   => $doctorRole->id,
                'is_active' => true,
            ]
        );

        $doctorAmira = Doctor::updateOrCreate(
            ['name_en' => 'Dr Amira Ahmed'],
            [
                'name_ar'                      => 'د. أميرة أحمد',
                'specialization_ar'            => 'أخصائى جلدية وتناسلية',
                'specialization_en'            => 'Dermatology & Venereology Specialist',
                'doctor_type'                  => 'specialist',
                'status'                       => 'active',
                'display_order'                => 3,
                'default_commission_percentage' => 20.00,
                'dermatology_commission'        => 40.00,
                'cosmetic_commission'           => 40.00,
                'followup_commission'           => 40.00,
                'user_id'                      => $userAmira->id,
            ]
        );

        // Weekly schedule: Sunday 6:00 PM – 9:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorAmira->id, 'day_of_week' => 1], // 1 = Sunday
            [
                'start_time' => '18:00:00',
                'end_time'   => '21:00:00',
                'is_active'  => true,
            ]
        );

        // Weekly schedule: Thursday 6:00 PM – 9:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorAmira->id, 'day_of_week' => 5], // 5 = Thursday
            [
                'start_time' => '18:00:00',
                'end_time'   => '21:00:00',
                'is_active'  => true,
            ]
        );

        // Service Rates (Specialist: 20% cosmetic, 20% laser)
        $this->seedServiceRates($doctorAmira->id, $cosmeticServiceIds, 20.00);
        $this->seedServiceRates($doctorAmira->id, $laserServiceIds, 20.00);

        // ── Dr Asmaa Hamdy ──────────────────────────────────
        $userAsmaa = User::firstOrCreate(
            ['email' => 'dr.asmaa@auraderma.com'],
            [
                'name'      => 'Dr Asmaa Hamdy',
                'password'  => Hash::make('password'),
                'role_id'   => $doctorRole->id,
                'is_active' => true,
            ]
        );

        $doctorAsmaa = Doctor::updateOrCreate(
            ['name_en' => 'Dr Asmaa Hamdy'],
            [
                'name_ar'                      => 'د. أسماء حمدي',
                'specialization_ar'            => 'استشارى الجلدية والتناسلية',
                'specialization_en'            => 'Dermatology & Venereology Consultant',
                'doctor_type'                  => 'consultant',
                'status'                       => 'active',
                'display_order'                => 4,
                'default_commission_percentage' => 25.00,
                'dermatology_commission'        => 50.00,
                'cosmetic_commission'           => 50.00,
                'followup_commission'           => 50.00,
                'user_id'                      => $userAsmaa->id,
            ]
        );

        // Weekly schedule: Monday 2:00 PM – 3:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorAsmaa->id, 'day_of_week' => 2], // 2 = Monday
            [
                'start_time' => '14:00:00',
                'end_time'   => '15:00:00',
                'is_active'  => true,
            ]
        );

        // Service Rates (Consultant: 30% cosmetic, 25% laser)
        $this->seedServiceRates($doctorAsmaa->id, $cosmeticServiceIds, 30.00);
        $this->seedServiceRates($doctorAsmaa->id, $laserServiceIds, 25.00);
    }

    /**
     * Seed service rates for a doctor.
     */
    private function seedServiceRates(int $doctorId, array $serviceIds, float $percentage): void
    {
        foreach ($serviceIds as $serviceId) {
            DoctorServiceRate::updateOrCreate(
                ['doctor_id' => $doctorId, 'service_id' => $serviceId],
                ['commission_percentage' => $percentage]
            );
        }
    }
}

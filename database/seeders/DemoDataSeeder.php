<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private array $doctorRecords = [];
    private array $serviceRecords = [];
    private array $patientRecords = [];

    public function run(): void
    {
        $this->command->info('Seeding demo data for all medical modules...');

        $this->seedDoctors();
        $this->seedServices();
        $this->seedPatients();
        $this->seedBookings();

        $this->command->info('Demo data seeding complete!');
    }

    // =========================================================================
    //  DOCTORS (9 total — 3 per module)
    // =========================================================================

    private function seedDoctors(): void
    {
        $this->command->info('  → Seeding doctors...');

        $doctorRole = Role::where('name', 'doctor')->first();
        if (! $doctorRole) {
            $this->command->warn('    Doctor role not found — skipping doctors.');
            return;
        }

        $doctors = [
            // ── Derma doctors (module = null) ───────────────────
            [
                'email'   => 'doctor-sarah-alrashid@demo.doctorato.com',
                'name'    => 'Dr. Sarah Al-Rashid',
                'name_ar' => 'د. سارة الراشد',
                'name_en' => 'Dr. Sarah Al-Rashid',
                'specialization_ar' => 'استشاري أمراض جلدية',
                'specialization_en' => 'Consultant Dermatologist',
                'doctor_type' => 'consultant',
                'module'      => 'derma',
                'dermatology_fee' => 300,
                'cosmetic_fee'    => 250,
            ],
            [
                'email'   => 'doctor-omar-hasan@demo.doctorato.com',
                'name'    => 'Dr. Omar Hasan',
                'name_ar' => 'د. عمر حسن',
                'name_en' => 'Dr. Omar Hasan',
                'specialization_ar' => 'أخصائي أمراض جلدية',
                'specialization_en' => 'Specialist Dermatologist',
                'doctor_type' => 'specialist',
                'module'      => 'derma',
                'dermatology_fee' => 200,
                'cosmetic_fee'    => 180,
            ],
            [
                'email'   => 'doctor-layla-nour@demo.doctorato.com',
                'name'    => 'Dr. Layla Nour',
                'name_ar' => 'د. ليلى نور',
                'name_en' => 'Dr. Layla Nour',
                'specialization_ar' => 'أخصائي أمراض جلدية',
                'specialization_en' => 'Specialist Dermatologist',
                'doctor_type' => 'specialist',
                'module'      => 'derma',
                'dermatology_fee' => 200,
                'cosmetic_fee'    => 180,
            ],

            // ── Dental doctors ──────────────────────────────────
            [
                'email'   => 'doctor-khalid-alamri@demo.doctorato.com',
                'name'    => 'Dr. Khalid Al-Amri',
                'name_ar' => 'د. خالد العمري',
                'name_en' => 'Dr. Khalid Al-Amri',
                'specialization_ar' => 'استشاري طب أسنان',
                'specialization_en' => 'Consultant Dentist',
                'doctor_type' => 'consultant',
                'module'      => 'dental',
                'dental_consultation_fee' => 350,
            ],
            [
                'email'   => 'doctor-nada-saleh@demo.doctorato.com',
                'name'    => 'Dr. Nada Saleh',
                'name_ar' => 'د. ندى صالح',
                'name_en' => 'Dr. Nada Saleh',
                'specialization_ar' => 'أخصائي طب أسنان',
                'specialization_en' => 'Specialist Dentist',
                'doctor_type' => 'specialist',
                'module'      => 'dental',
                'dental_consultation_fee' => 250,
            ],
            [
                'email'   => 'doctor-faisal-qasim@demo.doctorato.com',
                'name'    => 'Dr. Faisal Qasim',
                'name_ar' => 'د. فيصل قاسم',
                'name_en' => 'Dr. Faisal Qasim',
                'specialization_ar' => 'أخصائي طب أسنان',
                'specialization_en' => 'Specialist Dentist',
                'doctor_type' => 'specialist',
                'module'      => 'dental',
                'dental_consultation_fee' => 250,
            ],

            // ── Pediatric doctors ───────────────────────────────
            [
                'email'   => 'doctor-huda-mansour@demo.doctorato.com',
                'name'    => 'Dr. Huda Mansour',
                'name_ar' => 'د. هدى منصور',
                'name_en' => 'Dr. Huda Mansour',
                'specialization_ar' => 'استشاري طب أطفال',
                'specialization_en' => 'Consultant Pediatrician',
                'doctor_type' => 'consultant',
                'module'      => 'pediatric',
                'pediatric_consultation_fee' => 300,
            ],
            [
                'email'   => 'doctor-youssef-karim@demo.doctorato.com',
                'name'    => 'Dr. Youssef Karim',
                'name_ar' => 'د. يوسف كريم',
                'name_en' => 'Dr. Youssef Karim',
                'specialization_ar' => 'أخصائي طب أطفال',
                'specialization_en' => 'Specialist Pediatrician',
                'doctor_type' => 'specialist',
                'module'      => 'pediatric',
                'pediatric_consultation_fee' => 200,
            ],
            [
                'email'   => 'doctor-mariam-taha@demo.doctorato.com',
                'name'    => 'Dr. Mariam Taha',
                'name_ar' => 'د. مريم طه',
                'name_en' => 'Dr. Mariam Taha',
                'specialization_ar' => 'أخصائي طب أطفال',
                'specialization_en' => 'Specialist Pediatrician',
                'doctor_type' => 'specialist',
                'module'      => 'pediatric',
                'pediatric_consultation_fee' => 200,
            ],
        ];

        foreach ($doctors as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'password'  => Hash::make('password'),
                    'role_id'   => $doctorRole->id,
                    'is_active' => true,
                ]
            );

            $doctor = Doctor::updateOrCreate(
                ['name_en' => $data['name_en']],
                [
                    'user_id'            => $user->id,
                    'name_ar'            => $data['name_ar'],
                    'name_en'            => $data['name_en'],
                    'specialization_ar'  => $data['specialization_ar'],
                    'specialization_en'  => $data['specialization_en'],
                    'doctor_type'        => $data['doctor_type'],
                    'module'             => $data['module'],
                    'status'             => 'active',
                    'dermatology_fee'    => $data['dermatology_fee'] ?? null,
                    'cosmetic_fee'       => $data['cosmetic_fee'] ?? null,
                    'dental_consultation_fee'    => $data['dental_consultation_fee'] ?? null,
                    'pediatric_consultation_fee' => $data['pediatric_consultation_fee'] ?? null,
                ]
            );

            // Schedule: Sun-Thu (days 1-5), with slight variations
            $schedules = [
                ['days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:00'],
            ];

            // Consultants work slightly different hours
            if ($data['doctor_type'] === 'consultant') {
                $schedules = [
                    ['days' => [1, 2, 3], 'start' => '09:00', 'end' => '15:00'],
                    ['days' => [4, 5],    'start' => '12:00', 'end' => '18:00'],
                ];
            }

            foreach ($schedules as $sched) {
                foreach ($sched['days'] as $day) {
                    DoctorSchedule::updateOrCreate(
                        ['doctor_id' => $doctor->id, 'day_of_week' => $day],
                        ['start_time' => $sched['start'], 'end_time' => $sched['end'], 'is_active' => true]
                    );
                }
            }

            $this->doctorRecords[] = $doctor;
        }

        $this->command->info('    Created/updated ' . count($this->doctorRecords) . ' doctors with schedules.');
    }

    // =========================================================================
    //  SERVICES (categories + services for each module)
    // =========================================================================

    private function seedServices(): void
    {
        $this->command->info('  → Seeding services...');

        $modules = [
            // ── Derma services ──────────────────
            [
                'module' => 'derma',
                'categories' => [
                    [
                        'name_ar' => 'الليزر', 'name_en' => 'Laser', 'slug' => 'laser',
                        'services' => [
                            ['name_ar' => 'إزالة الشعر بالليزر', 'name_en' => 'Laser Hair Removal', 'slug' => 'laser-hair-removal', 'price' => 300, 'duration' => 30],
                            ['name_ar' => 'تجديد البشرة', 'name_en' => 'Skin Rejuvenation', 'slug' => 'skin-rejuvenation', 'price' => 500, 'duration' => 45],
                            ['name_ar' => 'إزالة الوشم', 'name_en' => 'Tattoo Removal', 'slug' => 'tattoo-removal', 'price' => 400, 'duration' => 30],
                        ],
                    ],
                    [
                        'name_ar' => 'التجميل', 'name_en' => 'Cosmetics', 'slug' => 'cosmetics',
                        'services' => [
                            ['name_ar' => 'بوتوكس', 'name_en' => 'Botox', 'slug' => 'botox', 'price' => 800, 'duration' => 20],
                            ['name_ar' => 'فيلر', 'name_en' => 'Filler', 'slug' => 'filler', 'price' => 1200, 'duration' => 30],
                            ['name_ar' => 'تقشير كيميائي', 'name_en' => 'Chemical Peel', 'slug' => 'chemical-peel', 'price' => 600, 'duration' => 40],
                        ],
                    ],
                    [
                        'name_ar' => 'العلاج', 'name_en' => 'Treatment', 'slug' => 'treatment',
                        'services' => [
                            ['name_ar' => 'علاج حب الشباب', 'name_en' => 'Acne Treatment', 'slug' => 'acne-treatment', 'price' => 250, 'duration' => 30],
                            ['name_ar' => 'علاج الإكزيما', 'name_en' => 'Eczema Treatment', 'slug' => 'eczema-treatment', 'price' => 200, 'duration' => 30],
                        ],
                    ],
                ],
            ],

            // ── Dental services ─────────────────────────────────
            [
                'module' => 'dental',
                'categories' => [
                    [
                        'name_ar' => 'تنظيف وعلاج', 'name_en' => 'Cleaning & Treatment', 'slug' => 'dental-cleaning-treatment',
                        'services' => [
                            ['name_ar' => 'تنظيف الأسنان', 'name_en' => 'Teeth Cleaning', 'slug' => 'teeth-cleaning', 'price' => 300, 'duration' => 30],
                            ['name_ar' => 'حشو', 'name_en' => 'Filling', 'slug' => 'dental-filling', 'price' => 400, 'duration' => 45],
                            ['name_ar' => 'علاج عصب', 'name_en' => 'Root Canal', 'slug' => 'root-canal', 'price' => 1500, 'duration' => 60],
                        ],
                    ],
                    [
                        'name_ar' => 'تقويم وتجميل', 'name_en' => 'Orthodontics', 'slug' => 'dental-orthodontics',
                        'services' => [
                            ['name_ar' => 'تقويم أسنان', 'name_en' => 'Braces', 'slug' => 'braces', 'price' => 15000, 'duration' => 60],
                            ['name_ar' => 'تبييض الأسنان', 'name_en' => 'Teeth Whitening', 'slug' => 'teeth-whitening', 'price' => 2000, 'duration' => 45],
                            ['name_ar' => 'فينير', 'name_en' => 'Veneer', 'slug' => 'dental-veneer', 'price' => 3000, 'duration' => 45],
                        ],
                    ],
                    [
                        'name_ar' => 'جراحة', 'name_en' => 'Surgery', 'slug' => 'dental-surgery',
                        'services' => [
                            ['name_ar' => 'خلع ضرس العقل', 'name_en' => 'Wisdom Tooth Extraction', 'slug' => 'wisdom-tooth-extraction', 'price' => 800, 'duration' => 45],
                            ['name_ar' => 'زراعة أسنان', 'name_en' => 'Implant', 'slug' => 'dental-implant', 'price' => 5000, 'duration' => 90],
                        ],
                    ],
                ],
            ],

            // ── Pediatric services ──────────────────────────────
            [
                'module' => 'pediatric',
                'categories' => [
                    [
                        'name_ar' => 'التطعيمات', 'name_en' => 'Vaccinations', 'slug' => 'pediatric-vaccinations',
                        'services' => [
                            ['name_ar' => 'تطعيم الالتهاب الكبدي ب', 'name_en' => 'Hepatitis B Vaccine', 'slug' => 'hepatitis-b-vaccine', 'price' => 150, 'duration' => 15],
                            ['name_ar' => 'تطعيم الحصبة والنكاف والحصبة الألمانية', 'name_en' => 'MMR Vaccine', 'slug' => 'mmr-vaccine', 'price' => 200, 'duration' => 15],
                            ['name_ar' => 'تطعيم الدفتريا والتيتانوس والسعال الديكي', 'name_en' => 'DTaP Vaccine', 'slug' => 'dtap-vaccine', 'price' => 180, 'duration' => 15],
                        ],
                    ],
                    [
                        'name_ar' => 'الكشف والمتابعة', 'name_en' => 'Checkup & Follow-up', 'slug' => 'pediatric-checkup',
                        'services' => [
                            ['name_ar' => 'كشف دوري للطفل', 'name_en' => 'Well-child Visit', 'slug' => 'well-child-visit', 'price' => 250, 'duration' => 30],
                            ['name_ar' => 'تقييم النمو', 'name_en' => 'Growth Assessment', 'slug' => 'growth-assessment', 'price' => 200, 'duration' => 30],
                            ['name_ar' => 'فحص تطوري', 'name_en' => 'Developmental Screening', 'slug' => 'developmental-screening', 'price' => 300, 'duration' => 45],
                        ],
                    ],
                    [
                        'name_ar' => 'علاج الأطفال', 'name_en' => 'Pediatric Treatment', 'slug' => 'pediatric-treatment',
                        'services' => [
                            ['name_ar' => 'علاج التهاب الأذن', 'name_en' => 'Ear Infection Treatment', 'slug' => 'ear-infection-treatment', 'price' => 200, 'duration' => 20],
                            ['name_ar' => 'متابعة الربو', 'name_en' => 'Asthma Management', 'slug' => 'asthma-management', 'price' => 250, 'duration' => 30],
                        ],
                    ],
                ],
            ],
        ];

        $count = 0;
        foreach ($modules as $moduleData) {
            $module = $moduleData['module'];

            foreach ($moduleData['categories'] as $catData) {
                $category = ServiceCategory::updateOrCreate(
                    ['slug' => $catData['slug']],
                    [
                        'name_ar'  => $catData['name_ar'],
                        'name_en'  => $catData['name_en'],
                        'module'   => $module,
                    ]
                );

                foreach ($catData['services'] as $svcData) {
                    $service = Service::updateOrCreate(
                        ['slug' => $svcData['slug']],
                        [
                            'category_id'            => $category->id,
                            'name_ar'                => $svcData['name_ar'],
                            'name_en'                => $svcData['name_en'],
                            'price'                  => $svcData['price'],
                            'default_sessions'       => 1,
                            'session_duration_minutes' => $svcData['duration'],
                            'bookable'               => true,
                            'show_on_website'        => true,
                            'status'                 => 'active',
                            'module'                 => $module,
                        ]
                    );

                    $this->serviceRecords[$module ?? 'derma'][] = $service;
                    $count++;
                }
            }
        }

        $this->command->info("    Created/updated {$count} services across all modules.");
    }

    // =========================================================================
    //  PATIENTS (15 total — 5 per module type)
    // =========================================================================

    private function seedPatients(): void
    {
        $this->command->info('  → Seeding patients...');

        $patients = [
            // ── Derma patients (5) ──────────────────────────────
            ['full_name' => 'فاطمة أحمد محمود',  'phone' => '01012345001', 'gender' => 'female', 'dob' => '1990-03-15', 'type' => 'derma'],
            ['full_name' => 'محمد علي إبراهيم',  'phone' => '01012345002', 'gender' => 'male',   'dob' => '1985-07-22', 'type' => 'derma'],
            ['full_name' => 'نورا حسين سالم',    'phone' => '01012345003', 'gender' => 'female', 'dob' => '1995-11-08', 'type' => 'derma'],
            ['full_name' => 'أحمد خالد عبدالله', 'phone' => '01012345004', 'gender' => 'male',   'dob' => '1988-01-30', 'type' => 'derma'],
            ['full_name' => 'سارة يوسف حسن',    'phone' => '01012345005', 'gender' => 'female', 'dob' => '1992-06-12', 'type' => 'derma'],

            // ── Dental patients (5) ─────────────────────────────
            ['full_name' => 'عمر محمد الشريف',   'phone' => '01112345006', 'gender' => 'male',   'dob' => '1980-04-18', 'type' => 'dental',
                'dental' => ['has_dental_anxiety' => true, 'dental_anxiety_level' => 'moderate', 'is_smoker' => true, 'smoking_frequency' => 'daily']],
            ['full_name' => 'هالة عبدالرحمن',    'phone' => '01112345007', 'gender' => 'female', 'dob' => '1993-09-25', 'type' => 'dental',
                'dental' => ['has_heart_condition' => true]],
            ['full_name' => 'كريم مصطفى نور',   'phone' => '01112345008', 'gender' => 'male',   'dob' => '1975-12-03', 'type' => 'dental',
                'dental' => ['has_diabetes' => true, 'diabetes_type' => 'type_2']],
            ['full_name' => 'منى سعيد الفقي',   'phone' => '01112345009', 'gender' => 'female', 'dob' => '1987-02-14', 'type' => 'dental'],
            ['full_name' => 'ياسر حمدي توفيق',  'phone' => '01112345010', 'gender' => 'male',   'dob' => '1991-08-07', 'type' => 'dental'],

            // ── Pediatric patients (5) — young DOBs + guardian info
            ['full_name' => 'آدم حسام الدين',    'phone' => '01212345011', 'gender' => 'male',   'dob' => '2022-05-10', 'type' => 'pediatric',
                'guardian' => ['guardian_name' => 'حسام الدين محمد', 'guardian_relation' => 'father', 'guardian_phone' => '01212345111']],
            ['full_name' => 'ملك أشرف سامي',    'phone' => '01212345012', 'gender' => 'female', 'dob' => '2021-08-20', 'type' => 'pediatric',
                'guardian' => ['guardian_name' => 'سامية أشرف', 'guardian_relation' => 'mother', 'guardian_phone' => '01212345112']],
            ['full_name' => 'يوسف طارق عمران',  'phone' => '01212345013', 'gender' => 'male',   'dob' => '2023-01-15', 'type' => 'pediatric',
                'guardian' => ['guardian_name' => 'طارق عمران', 'guardian_relation' => 'father', 'guardian_phone' => '01212345113']],
            ['full_name' => 'جنى وليد حسن',     'phone' => '01212345014', 'gender' => 'female', 'dob' => '2020-11-03', 'type' => 'pediatric',
                'guardian' => ['guardian_name' => 'رانيا حسن', 'guardian_relation' => 'mother', 'guardian_phone' => '01212345114']],
            ['full_name' => 'زياد مروان خليل',  'phone' => '01212345015', 'gender' => 'male',   'dob' => '2024-03-28', 'type' => 'pediatric',
                'guardian' => ['guardian_name' => 'مروان خليل', 'guardian_relation' => 'father', 'guardian_phone' => '01212345115']],
        ];

        foreach ($patients as $data) {
            // Skip if patient already exists with this phone
            if (Patient::where('phone', $data['phone'])->exists()) {
                $this->patientRecords[] = Patient::where('phone', $data['phone'])->first();
                continue;
            }

            $attributes = [
                'full_name'     => $data['full_name'],
                'phone'         => $data['phone'],
                'gender'        => $data['gender'],
                'date_of_birth' => $data['dob'],
            ];

            // Merge dental-specific fields
            if (isset($data['dental'])) {
                $attributes = array_merge($attributes, $data['dental']);
            }

            // Merge guardian fields for pediatric
            if (isset($data['guardian'])) {
                $attributes = array_merge($attributes, $data['guardian']);
            }

            $patient = new Patient($attributes);
            $patient->file_number = Patient::generateFileNumber();
            $patient->is_active = true;
            $patient->save();

            $this->patientRecords[] = $patient;
        }

        $this->command->info('    Created/verified ' . count($this->patientRecords) . ' patients.');
    }

    // =========================================================================
    //  BOOKINGS (15 total — 5 per module)
    // =========================================================================

    private function seedBookings(): void
    {
        $this->command->info('  → Seeding bookings with appointments...');

        // Skip if demo bookings already exist (avoid duplicate appointment conflicts)
        if (Booking::where('source', 'clinic')->whereIn('module', ['derma', 'dental', 'pediatric'])->count() >= 15) {
            $this->command->info('    Demo bookings already exist — skipping.');
            return;
        }

        if (count($this->patientRecords) < 15 || count($this->doctorRecords) < 9) {
            $this->command->warn('    Not enough patients or doctors — skipping bookings.');
            return;
        }

        $dermaServices     = $this->serviceRecords['derma'] ?? [];
        $dentalServices    = $this->serviceRecords['dental'] ?? [];
        $pediatricServices = $this->serviceRecords['pediatric'] ?? [];

        // Derma doctors (first 3), dental (next 3), pediatric (last 3)
        $dermaDoctors     = array_slice($this->doctorRecords, 0, 3);
        $dentalDoctors    = array_slice($this->doctorRecords, 3, 3);
        $pediatricDoctors = array_slice($this->doctorRecords, 6, 3);

        $statuses = ['confirmed', 'confirmed', 'in_progress', 'in_progress', 'completed'];
        $bookingCount = 0;
        $baseDate = Carbon::today();

        // ── Derma bookings (patients 0-4) ───────────────────
        for ($i = 0; $i < 5; $i++) {
            $patient = $this->patientRecords[$i];
            $doctor  = $dermaDoctors[$i % 3];
            $status  = $statuses[$i];
            $isConsultation = $i < 3; // first 3 = consultation, last 2 = service

            $booking = Booking::create([
                'patient_id'     => $patient->id,
                'booking_number' => Booking::generateBookingNumber(),
                'source'         => 'clinic',
                'module'         => 'derma',
                'booking_type'   => $isConsultation ? 'dermatology_consultation' : 'service',
                'full_name'      => $patient->full_name,
                'phone'          => $patient->phone,
                'status'         => $status,
            ]);

            if ($isConsultation) {
                // Consultation booking — fee comes from doctor
                $fee = $doctor->dermatology_fee ?? 200;
                $bs = BookingService::create([
                    'booking_id'     => $booking->id,
                    'doctor_id'      => $doctor->id,
                    'sessions_count' => 1,
                    'unit_price'     => $fee,
                    'total_price'    => $fee,
                    'status'         => $status === 'completed' ? 'completed' : 'in_progress',
                ]);
            } else {
                // Service booking — pick a random derma service
                $service = $dermaServices[array_rand($dermaServices)] ?? null;
                if ($service) {
                    $bs = BookingService::create([
                        'booking_id'     => $booking->id,
                        'service_id'     => $service->id,
                        'doctor_id'      => $doctor->id,
                        'sessions_count' => 1,
                        'unit_price'     => $service->price,
                        'total_price'    => $service->price,
                        'status'         => $status === 'completed' ? 'completed' : 'in_progress',
                    ]);
                }
            }

            // Appointment
            if (isset($bs)) {
                $appointmentDate = $baseDate->copy()->addDays(rand(1, 28));
                BookingAppointment::create([
                    'booking_id'         => $booking->id,
                    'booking_service_id' => $bs->id,
                    'doctor_id'          => $doctor->id,
                    'appointment_date'   => $appointmentDate,
                    'start_time'         => '10:00',
                    'end_time'           => '10:30',
                    'session_number'     => 1,
                    'status'             => $status === 'completed' ? 'completed' : 'scheduled',
                ]);
            }

            $bookingCount++;
        }

        // ── Dental bookings (patients 5-9) ──────────────────
        for ($i = 0; $i < 5; $i++) {
            $patient = $this->patientRecords[$i + 5];
            $doctor  = $dentalDoctors[$i % 3];
            $status  = $statuses[$i];
            $isConsultation = $i < 3;

            $booking = Booking::create([
                'patient_id'     => $patient->id,
                'booking_number' => Booking::generateBookingNumber(),
                'source'         => 'clinic',
                'module'         => 'dental',
                'booking_type'   => $isConsultation ? 'dental_consultation' : 'dental_service',
                'full_name'      => $patient->full_name,
                'phone'          => $patient->phone,
                'status'         => $status,
            ]);

            if ($isConsultation) {
                $fee = $doctor->dental_consultation_fee ?? 250;
                $bs = BookingService::create([
                    'booking_id'     => $booking->id,
                    'doctor_id'      => $doctor->id,
                    'sessions_count' => 1,
                    'unit_price'     => $fee,
                    'total_price'    => $fee,
                    'status'         => $status === 'completed' ? 'completed' : 'in_progress',
                ]);
            } else {
                $service = $dentalServices[array_rand($dentalServices)] ?? null;
                if ($service) {
                    $bs = BookingService::create([
                        'booking_id'     => $booking->id,
                        'service_id'     => $service->id,
                        'doctor_id'      => $doctor->id,
                        'sessions_count' => 1,
                        'unit_price'     => $service->price,
                        'total_price'    => $service->price,
                        'status'         => $status === 'completed' ? 'completed' : 'in_progress',
                    ]);
                }
            }

            if (isset($bs)) {
                $appointmentDate = $baseDate->copy()->addDays(rand(1, 28));
                BookingAppointment::create([
                    'booking_id'         => $booking->id,
                    'booking_service_id' => $bs->id,
                    'doctor_id'          => $doctor->id,
                    'appointment_date'   => $appointmentDate,
                    'start_time'         => '11:00',
                    'end_time'           => '11:45',
                    'session_number'     => 1,
                    'status'             => $status === 'completed' ? 'completed' : 'scheduled',
                ]);
            }

            $bookingCount++;
        }

        // ── Pediatric bookings (patients 10-14) ─────────────
        for ($i = 0; $i < 5; $i++) {
            $patient = $this->patientRecords[$i + 10];
            $doctor  = $pediatricDoctors[$i % 3];
            $status  = $statuses[$i];
            $isConsultation = $i < 3;

            $booking = Booking::create([
                'patient_id'     => $patient->id,
                'booking_number' => Booking::generateBookingNumber(),
                'source'         => 'clinic',
                'module'         => 'pediatric',
                'booking_type'   => $isConsultation ? 'pediatric_consultation' : 'pediatric_service',
                'full_name'      => $patient->full_name,
                'phone'          => $patient->phone,
                'status'         => $status,
            ]);

            if ($isConsultation) {
                $fee = $doctor->pediatric_consultation_fee ?? 200;
                $bs = BookingService::create([
                    'booking_id'     => $booking->id,
                    'doctor_id'      => $doctor->id,
                    'sessions_count' => 1,
                    'unit_price'     => $fee,
                    'total_price'    => $fee,
                    'status'         => $status === 'completed' ? 'completed' : 'in_progress',
                ]);
            } else {
                $service = $pediatricServices[array_rand($pediatricServices)] ?? null;
                if ($service) {
                    $bs = BookingService::create([
                        'booking_id'     => $booking->id,
                        'service_id'     => $service->id,
                        'doctor_id'      => $doctor->id,
                        'sessions_count' => 1,
                        'unit_price'     => $service->price,
                        'total_price'    => $service->price,
                        'status'         => $status === 'completed' ? 'completed' : 'in_progress',
                    ]);
                }
            }

            if (isset($bs)) {
                $appointmentDate = $baseDate->copy()->addDays(rand(1, 28));
                BookingAppointment::create([
                    'booking_id'         => $booking->id,
                    'booking_service_id' => $bs->id,
                    'doctor_id'          => $doctor->id,
                    'appointment_date'   => $appointmentDate,
                    'start_time'         => '14:00',
                    'end_time'           => '14:30',
                    'session_number'     => 1,
                    'status'             => $status === 'completed' ? 'completed' : 'scheduled',
                ]);
            }

            $bookingCount++;
        }

        $this->command->info("    Created {$bookingCount} bookings with appointments.");
    }
}

<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\DentalChart;
use App\Models\DentalTreatment;
use App\Models\DentalXray;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PediatricAllergy;
use App\Models\PediatricGrowthRecord;
use App\Models\PediatricVaccination;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private array $doctorRecords = [];
    private array $serviceRecords = [];
    private array $patientRecords = [];
    private array $visitRecords = [];

    public function run(): void
    {
        $this->command->info('Seeding demo data for all medical modules...');

        $this->seedDoctors();
        $this->seedServices();
        $this->seedPatients();
        $this->seedBookings();
        $this->seedVisits();
        $this->seedInvoicesAndPayments();
        $this->seedPrescriptions();
        $this->seedDentalRecords();
        $this->seedPediatricRecords();

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

    // =========================================================================
    //  VISITS — Created for completed/in_progress bookings
    // =========================================================================

    private function seedVisits(): void
    {
        $this->command->info('  → Seeding visits...');

        try {
            $bookings = Booking::with(['bookingServices', 'appointments', 'patient'])
                ->where('source', 'clinic')
                ->whereIn('module', ['derma', 'dental', 'pediatric'])
                ->whereIn('status', ['in_progress', 'completed'])
                ->get();

            if ($bookings->isEmpty()) {
                $this->command->warn('    No eligible bookings for visits.');
                return;
            }

            $diagnosesByModule = [
                'derma' => [
                    ['dx' => 'التهاب جلدي تماسي', 'notes' => 'تم وصف كريم مرطب ومضاد التهاب، ومتابعة بعد أسبوع.'],
                    ['dx' => 'حب شباب', 'notes' => 'خطة علاج موضعي مع متابعة شهرية لتقييم الاستجابة.'],
                    ['dx' => 'الثعلبة', 'notes' => 'تم وصف محلول موضعي ومكمل غذائي مع جلسات متابعة.'],
                    ['dx' => 'صدفية', 'notes' => 'كريم كورتيزون خفيف مع مرطب يومي وتجنب المحفزات.'],
                ],
                'dental' => [
                    ['dx' => 'تسوس في الضرس العلوي الأيمن', 'notes' => 'تم تنظيف التسوس وحشوه بحشوة مركبة.'],
                    ['dx' => 'التهاب اللثة', 'notes' => 'تم إجراء تنظيف عميق ووصف غسول فم.'],
                    ['dx' => 'التهاب لب السن', 'notes' => 'يحتاج لعلاج عصب كامل بجلسات متعددة.'],
                ],
                'pediatric' => [
                    ['dx' => 'نزلة برد', 'notes' => 'أعراض بسيطة، تم وصف علاج عرضي مع راحة ورضاعة جيدة.'],
                    ['dx' => 'فحص نمو روتيني', 'notes' => 'النمو ضمن المعدل الطبيعي، متابعة بعد شهرين.'],
                    ['dx' => 'تطعيمات دورية', 'notes' => 'تم إعطاء التطعيمات حسب الجدول المعتمد.'],
                    ['dx' => 'حساسية طعام', 'notes' => 'توصية بتجنب الطعام المسبب وكتابة مضاد هستامين عند الحاجة.'],
                ],
            ];

            $count = 0;
            foreach ($bookings as $booking) {
                if (Visit::where('booking_id', $booking->id)->exists()) {
                    // Already seeded — reuse for downstream methods
                    foreach (Visit::where('booking_id', $booking->id)->get() as $v) {
                        $this->visitRecords[] = $v;
                    }
                    continue;
                }

                $module   = $booking->module;
                $patient  = $booking->patient;
                $services = $booking->bookingServices;

                foreach ($booking->appointments as $appointment) {
                    $bookingService = $services->firstWhere('id', $appointment->booking_service_id) ?? $services->first();
                    $doctorId = $appointment->doctor_id ?? ($bookingService->doctor_id ?? null);

                    $visitType = ($bookingService && $bookingService->service_id) ? 'session' : 'consultation';
                    $visitStatus = $booking->status === 'completed' ? 'completed' : 'in_progress';

                    $dxData = $diagnosesByModule[$module][array_rand($diagnosesByModule[$module])];

                    $apptDate = Carbon::parse($appointment->appointment_date);
                    $startedAt = $apptDate->copy()->setTimeFromTimeString(
                        substr($appointment->start_time, 0, 8) ?: '10:00:00'
                    );
                    $completedAt = $visitStatus === 'completed' ? $startedAt->copy()->addMinutes(30) : null;

                    $visit = Visit::create([
                        'patient_id'             => $patient->id,
                        'doctor_id'              => $doctorId,
                        'booking_id'             => $booking->id,
                        'booking_appointment_id' => $appointment->id,
                        'module'                 => $module,
                        'visit_type'             => $visitType,
                        'service_id'             => $bookingService->service_id ?? null,
                        'session_number'         => $appointment->session_number,
                        'status'                 => $visitStatus,
                        'visit_date'             => $apptDate->toDateString(),
                        'scheduled_time'         => $appointment->start_time,
                        'started_at'             => $startedAt,
                        'completed_at'           => $completedAt,
                        'diagnosis'              => $dxData['dx'],
                        'doctor_notes'           => $dxData['notes'],
                    ]);

                    $appointment->update(['visit_id' => $visit->id]);

                    $this->visitRecords[] = $visit;
                    $count++;
                }
            }

            $this->command->info("    Created {$count} visit records.");
        } catch (\Throwable $e) {
            $this->command->error('    Failed to seed visits: ' . $e->getMessage());
        }
    }

    // =========================================================================
    //  INVOICES & PAYMENTS — For each completed visit
    // =========================================================================

    private function seedInvoicesAndPayments(): void
    {
        $this->command->info('  → Seeding invoices and payments...');

        if (empty($this->visitRecords)) {
            // Load existing visits as fallback
            $this->visitRecords = Visit::whereNotNull('booking_id')->get()->all();
        }

        if (empty($this->visitRecords)) {
            $this->command->warn('    No visits available — skipping.');
            return;
        }

        try {
            $adminUser = User::where('email', 'admin@aura-clinic.net')->first()
                ?? User::where('email', 'admin@aura.com')->first()
                ?? User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->first();
            $adminId = $adminUser?->id;

            $paymentMethods = PaymentMethod::active()->get();
            if ($paymentMethods->isEmpty()) {
                $this->command->warn('    No active payment methods — skipping payments.');
                return;
            }

            $invoiceCount = 0;
            $paymentCount = 0;
            $seq = 0;

            foreach ($this->visitRecords as $visit) {
                if ($visit->status !== 'completed') continue;

                if (Invoice::where('visit_id', $visit->id)->exists()) continue;

                $seq++;
                $booking = $visit->booking;
                if (! $booking) continue;

                $bookingServices = $booking->bookingServices;
                $subtotal = (float) $bookingServices->sum('total_price');
                if ($subtotal <= 0) {
                    $subtotal = 200.00; // default fallback
                }

                $invoiceNumber = method_exists(Invoice::class, 'generateInvoiceNumber')
                    ? Invoice::generateInvoiceNumber()
                    : 'INV-' . date('Ym') . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

                $invoiceDate = $visit->visit_date ? Carbon::parse($visit->visit_date) : Carbon::today();

                $invoice = Invoice::create([
                    'invoice_number'  => $invoiceNumber,
                    'invoice_date'    => $invoiceDate,
                    'patient_id'      => $visit->patient_id,
                    'visit_id'        => $visit->id,
                    'booking_id'      => $booking->id,
                    'module'          => $visit->module,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount'      => 0,
                    'total'           => $subtotal,
                    'created_by'      => $adminId,
                    'notes'           => 'فاتورة تلقائية من الزيارة',
                ]);

                // Create invoice items — one per booking service
                foreach ($bookingServices as $bs) {
                    $descAr = $bs->service->name_ar ?? ($booking->booking_type === 'dermatology_consultation' ? 'كشف جلدية' :
                              ($booking->booking_type === 'dental_consultation' ? 'كشف أسنان' :
                              ($booking->booking_type === 'pediatric_consultation' ? 'كشف أطفال' : 'خدمة طبية')));
                    $descEn = $bs->service->name_en ?? ($booking->booking_type ?? 'Medical Service');

                    InvoiceItem::create([
                        'invoice_id'     => $invoice->id,
                        'description_ar' => $descAr,
                        'description_en' => $descEn,
                        'quantity'       => 1,
                        'unit_price'     => (float) $bs->unit_price,
                        'discount'       => 0,
                        'total'          => (float) $bs->total_price,
                    ]);
                }

                $invoiceCount++;

                // Create 1-2 payments (full or partial)
                $isFullPayment = (bool) random_int(0, 1);
                $method = $paymentMethods->random();

                if ($isFullPayment) {
                    Payment::create([
                        'invoice_id'        => $invoice->id,
                        'patient_id'        => $visit->patient_id,
                        'payment_method_id' => $method->id,
                        'amount'            => $subtotal,
                        'payment_date'      => $invoiceDate,
                        'reference_number'  => 'PAY-' . strtoupper(Str::random(6)),
                        'received_by'       => $adminId,
                    ]);
                    $paymentCount++;
                } else {
                    // Partial: 60-80% first, then remainder
                    $firstPct   = random_int(60, 80) / 100;
                    $firstAmount = round($subtotal * $firstPct, 2);
                    $remaining  = round($subtotal - $firstAmount, 2);

                    Payment::create([
                        'invoice_id'        => $invoice->id,
                        'patient_id'        => $visit->patient_id,
                        'payment_method_id' => $method->id,
                        'amount'            => $firstAmount,
                        'payment_date'      => $invoiceDate,
                        'reference_number'  => 'PAY-' . strtoupper(Str::random(6)),
                        'received_by'       => $adminId,
                    ]);
                    $paymentCount++;

                    if ($remaining > 0) {
                        Payment::create([
                            'invoice_id'        => $invoice->id,
                            'patient_id'        => $visit->patient_id,
                            'payment_method_id' => $paymentMethods->random()->id,
                            'amount'            => $remaining,
                            'payment_date'      => $invoiceDate->copy()->addDays(random_int(2, 5)),
                            'reference_number'  => 'PAY-' . strtoupper(Str::random(6)),
                            'received_by'       => $adminId,
                        ]);
                        $paymentCount++;
                    }
                }

                // Recalculate status
                if (method_exists($invoice, 'recalculateStatus')) {
                    $invoice->recalculateStatus();
                } else {
                    $totalPaid = $invoice->payments()->sum('amount');
                    $invoice->paid_amount = $totalPaid;
                    $invoice->status = $totalPaid >= $invoice->total ? 'paid' : 'partial';
                    $invoice->save();
                }
            }

            $this->command->info("    Created {$invoiceCount} invoices and {$paymentCount} payments.");
        } catch (\Throwable $e) {
            $this->command->error('    Failed to seed invoices/payments: ' . $e->getMessage());
        }
    }

    // =========================================================================
    //  PRESCRIPTIONS — For each completed visit
    // =========================================================================

    private function seedPrescriptions(): void
    {
        $this->command->info('  → Seeding prescriptions...');

        if (empty($this->visitRecords)) {
            $this->visitRecords = Visit::whereNotNull('booking_id')->get()->all();
        }

        if (empty($this->visitRecords)) {
            $this->command->warn('    No visits — skipping.');
            return;
        }

        try {
            $medsByModule = [
                'derma' => [
                    ['name' => 'Cetaphil Moisturizer', 'dosage' => '1 application', 'frequency' => 'مرتين يومياً', 'duration' => '14 يوم', 'notes' => 'استخدام موضعي على المناطق المتأثرة'],
                    ['name' => 'Isotretinoin 20mg',    'dosage' => '1 tab',          'frequency' => 'مرة يومياً',   'duration' => 'شهر واحد', 'notes' => 'يؤخذ مع الطعام'],
                    ['name' => 'Clindamycin Gel 1%',   'dosage' => '1 application', 'frequency' => 'مرتين يومياً', 'duration' => '10 أيام',  'notes' => 'استخدام موضعي على الوجه'],
                    ['name' => 'Adapalene 0.1%',       'dosage' => '1 application', 'frequency' => 'ليلاً',        'duration' => 'أسبوعين',  'notes' => 'تجنب التعرض للشمس'],
                    ['name' => 'Doxycycline 100mg',    'dosage' => '1 cap',         'frequency' => 'مرتين يومياً', 'duration' => '7 أيام',   'notes' => 'يؤخذ بعد الطعام'],
                ],
                'dental' => [
                    ['name' => 'Amoxicillin 500mg',        'dosage' => '1 cap',       'frequency' => '3 مرات يومياً', 'duration' => '7 أيام',  'notes' => 'لمكافحة الالتهاب البكتيري'],
                    ['name' => 'Ibuprofen 400mg',           'dosage' => '1 tab',       'frequency' => 'عند الحاجة',   'duration' => '5 أيام',  'notes' => 'لتسكين الألم'],
                    ['name' => 'Chlorhexidine Mouthwash',   'dosage' => '10ml',        'frequency' => 'مرتين يومياً', 'duration' => '10 أيام', 'notes' => 'مضمضة لمدة 30 ثانية'],
                    ['name' => 'Paracetamol 500mg',         'dosage' => '1 tab',       'frequency' => 'عند الحاجة',   'duration' => '3 أيام',  'notes' => 'لا يتجاوز 4 أقراص يومياً'],
                    ['name' => 'Sensodyne Toothpaste',      'dosage' => 'قدر حبة بازلاء', 'frequency' => 'مرتين يومياً', 'duration' => 'أسبوعين', 'notes' => 'لتقليل حساسية الأسنان'],
                ],
                'pediatric' => [
                    ['name' => 'Paracetamol Syrup 120mg/5ml', 'dosage' => '5ml',     'frequency' => 'عند الحاجة',    'duration' => '3 أيام',  'notes' => 'كل 6 ساعات عند الحرارة'],
                    ['name' => 'Vitamin D Drops',              'dosage' => '3 drops', 'frequency' => 'مرة يومياً',    'duration' => '30 يوم',  'notes' => 'يُعطى صباحاً'],
                    ['name' => 'Iron Syrup',                   'dosage' => '5ml',     'frequency' => 'مرة يومياً',    'duration' => 'شهر',     'notes' => 'بين الوجبات مع عصير طبيعي'],
                    ['name' => 'Amoxicillin Syrup 250mg/5ml',  'dosage' => '5ml',     'frequency' => '3 مرات يومياً', 'duration' => '7 أيام',  'notes' => 'رج جيداً قبل الاستخدام'],
                    ['name' => 'Cetirizine Drops',             'dosage' => '5 drops', 'frequency' => 'مرة يومياً',    'duration' => '5 أيام',  'notes' => 'للحساسية'],
                ],
            ];

            $prescriptionCount = 0;
            $itemCount = 0;

            foreach ($this->visitRecords as $visit) {
                if ($visit->status !== 'completed') continue;

                if (Prescription::where('visit_id', $visit->id)->exists()) continue;

                $module = $visit->module ?? 'derma';
                $meds   = $medsByModule[$module] ?? $medsByModule['derma'];

                $numPrescriptions = random_int(1, 2);
                for ($p = 0; $p < $numPrescriptions; $p++) {
                    $prescription = Prescription::create([
                        'visit_id'   => $visit->id,
                        'patient_id' => $visit->patient_id,
                        'doctor_id'  => $visit->doctor_id,
                        'diagnosis'  => $visit->diagnosis,
                        'notes'      => 'خطة العلاج: ' . ($visit->doctor_notes ?? 'يرجى اتباع التعليمات'),
                    ]);
                    $prescriptionCount++;

                    // 2-4 items
                    $numItems = random_int(2, 4);
                    $keys = array_rand($meds, min($numItems, count($meds)));
                    $keys = is_array($keys) ? $keys : [$keys];

                    $sort = 0;
                    foreach ($keys as $k) {
                        $med = $meds[$k];
                        PrescriptionItem::create([
                            'prescription_id' => $prescription->id,
                            'medication_name' => $med['name'],
                            'dosage'          => $med['dosage'],
                            'frequency'       => $med['frequency'],
                            'duration'        => $med['duration'],
                            'instructions'    => $med['notes'],
                            'sort_order'      => $sort++,
                        ]);
                        $itemCount++;
                    }
                }
            }

            $this->command->info("    Created {$prescriptionCount} prescriptions with {$itemCount} items.");
        } catch (\Throwable $e) {
            $this->command->error('    Failed to seed prescriptions: ' . $e->getMessage());
        }
    }

    // =========================================================================
    //  DENTAL RECORDS — Charts, Treatments, X-rays for dental patients
    // =========================================================================

    private function seedDentalRecords(): void
    {
        $this->command->info('  → Seeding dental records...');

        try {
            // Dental patients are indexes 5..9 (5 dental patients)
            $dentalPatients = array_slice($this->patientRecords, 5, 5);
            if (empty($dentalPatients)) {
                $dentalPatients = Patient::whereHas('bookings', fn ($q) => $q->where('module', 'dental'))
                    ->limit(5)->get()->all();
            }

            if (empty($dentalPatients)) {
                $this->command->warn('    No dental patients — skipping.');
                return;
            }

            $dentalDoctors = array_slice($this->doctorRecords, 3, 3);
            if (empty($dentalDoctors)) {
                $dentalDoctors = Doctor::where('module', 'dental')->limit(3)->get()->all();
            }
            if (empty($dentalDoctors)) {
                $this->command->warn('    No dental doctors — skipping.');
                return;
            }

            $teeth = [11, 12, 16, 17, 26, 36, 37, 46];
            $surfaces = [DentalChart::SURFACE_MESIAL, DentalChart::SURFACE_DISTAL, DentalChart::SURFACE_OCCLUSAL, DentalChart::SURFACE_BUCCAL, DentalChart::SURFACE_LINGUAL];
            $conditions = [
                DentalChart::CONDITION_DECAYED,
                DentalChart::CONDITION_FILLED,
                DentalChart::CONDITION_CROWN,
                DentalChart::CONDITION_ROOT_CANAL,
                DentalChart::CONDITION_HEALTHY,
            ];
            $treatmentTypes = [
                DentalTreatment::TYPE_FILLING,
                DentalTreatment::TYPE_CLEANING,
                DentalTreatment::TYPE_ROOT_CANAL,
                DentalTreatment::TYPE_CROWN,
                DentalTreatment::TYPE_EXTRACTION,
            ];
            $xrayTypes = [
                DentalXray::TYPE_PANORAMIC,
                DentalXray::TYPE_PERIAPICAL,
                DentalXray::TYPE_BITEWING,
            ];

            $chartCount = 0;
            $treatmentCount = 0;
            $xrayCount = 0;

            foreach ($dentalPatients as $patient) {
                // Skip if patient already has dental records seeded
                $hasTreatments = DentalTreatment::where('patient_id', $patient->id)->exists();
                $hasXrays = DentalXray::where('patient_id', $patient->id)->exists();
                if ($hasTreatments && $hasXrays) {
                    continue;
                }

                $doctor = $dentalDoctors[array_rand($dentalDoctors)];

                // Pull existing visits for this patient (dental module)
                $patientVisits = Visit::where('patient_id', $patient->id)
                    ->where('module', 'dental')
                    ->get();

                // ── Dental Chart entries (3-5 teeth) ──
                $numCharts = random_int(3, 5);
                $selectedTeeth = array_rand(array_flip($teeth), $numCharts);
                $selectedTeeth = is_array($selectedTeeth) ? $selectedTeeth : [$selectedTeeth];

                foreach ($selectedTeeth as $tooth) {
                    if (DentalChart::where('patient_id', $patient->id)->where('tooth_number', $tooth)->exists()) {
                        continue;
                    }
                    $condition = $conditions[array_rand($conditions)];
                    $toothSurfaces = null;
                    if (in_array($condition, [DentalChart::CONDITION_FILLED, DentalChart::CONDITION_DECAYED])) {
                        $numSurf = random_int(1, 2);
                        $keys = array_rand(array_flip($surfaces), $numSurf);
                        $toothSurfaces = is_array($keys) ? $keys : [$keys];
                    }

                    DentalChart::create([
                        'patient_id'   => $patient->id,
                        'tooth_number' => $tooth,
                        'condition'    => $condition,
                        'surfaces'     => $toothSurfaces,
                        'notes'        => 'ملاحظة: السن بحالة ' . $condition,
                        'status'       => $condition === DentalChart::CONDITION_MISSING ? 'missing' : 'present',
                    ]);
                    $chartCount++;
                }

                // ── Dental Treatments (2-3 per patient, linked to visits when available) ──
                if ($hasTreatments) {
                    $numTreatments = 0;
                } else {
                    $numTreatments = random_int(2, 3);
                }
                for ($t = 0; $t < $numTreatments; $t++) {
                    $tooth = $teeth[array_rand($teeth)];
                    $type  = $treatmentTypes[array_rand($treatmentTypes)];
                    $isCompleted = (bool) random_int(0, 1);
                    $status = $isCompleted ? DentalTreatment::STATUS_COMPLETED : DentalTreatment::STATUS_PLANNED;

                    $treatmentSurfaces = null;
                    if ($type === DentalTreatment::TYPE_FILLING) {
                        $numSurf = random_int(1, 2);
                        $keys = array_rand(array_flip($surfaces), $numSurf);
                        $treatmentSurfaces = is_array($keys) ? $keys : [$keys];
                    }

                    $linkedVisit = $patientVisits->isNotEmpty() ? $patientVisits->random() : null;

                    DentalTreatment::create([
                        'patient_id'    => $patient->id,
                        'doctor_id'     => $doctor->id,
                        'visit_id'      => $linkedVisit?->id,
                        'tooth_number'  => $tooth,
                        'treatment_type' => $type,
                        'surfaces'      => $treatmentSurfaces,
                        'description'   => 'علاج ' . $type . ' للسن رقم ' . $tooth,
                        'cost'          => random_int(300, 2000),
                        'lab_cost'      => 0,
                        'status'        => $status,
                        'completed_at'  => $isCompleted ? Carbon::now()->subDays(random_int(1, 60))->toDateString() : null,
                        'notes'         => 'تمت خطة العلاج بناءً على الفحص السريري',
                    ]);
                    $treatmentCount++;
                }

                // ── X-rays (1-2 per patient) ──
                if ($hasXrays) {
                    $numXrays = 0;
                } else {
                    $numXrays = random_int(1, 2);
                }
                for ($x = 0; $x < $numXrays; $x++) {
                    $type = $xrayTypes[array_rand($xrayTypes)];
                    DentalXray::create([
                        'patient_id'   => $patient->id,
                        'doctor_id'    => $doctor->id,
                        'type'         => $type,
                        'image_path'   => 'demo/xrays/' . $type . '-' . $patient->id . '.jpg',
                        'tooth_number' => $type === DentalXray::TYPE_PANORAMIC ? null : (string) $teeth[array_rand($teeth)],
                        'findings'     => 'النتائج: لا يوجد كسور ظاهرة، توجد علامات تسوس بسيط في بعض الأسنان.',
                        'notes'        => 'أخذت صورة بجودة عالية للمراجعة.',
                        'taken_date'   => Carbon::now()->subDays(random_int(5, 120))->toDateString(),
                    ]);
                    $xrayCount++;
                }
            }

            $this->command->info("    Created {$chartCount} chart entries, {$treatmentCount} treatments, {$xrayCount} x-rays.");
        } catch (\Throwable $e) {
            $this->command->error('    Failed to seed dental records: ' . $e->getMessage());
        }
    }

    // =========================================================================
    //  PEDIATRIC RECORDS — Vaccinations, Growth, Allergies
    // =========================================================================

    private function seedPediatricRecords(): void
    {
        $this->command->info('  → Seeding pediatric records...');

        try {
            // Pediatric patients are indexes 10..14
            $pediatricPatients = array_slice($this->patientRecords, 10, 5);
            if (empty($pediatricPatients)) {
                $pediatricPatients = Patient::whereHas('bookings', fn ($q) => $q->where('module', 'pediatric'))
                    ->limit(5)->get()->all();
            }

            if (empty($pediatricPatients)) {
                $this->command->warn('    No pediatric patients — skipping.');
                return;
            }

            $pediatricDoctors = array_slice($this->doctorRecords, 6, 3);
            if (empty($pediatricDoctors)) {
                $pediatricDoctors = Doctor::where('module', 'pediatric')->limit(3)->get()->all();
            }

            $manufacturers = ['GSK', 'Pfizer', 'Sanofi', 'Merck'];
            $injectionSites = ['Left thigh', 'Right thigh', 'Left arm', 'Right arm', 'Oral'];

            $vaccineCount = 0;
            $growthCount = 0;
            $allergyCount = 0;

            foreach ($pediatricPatients as $patient) {
                $hasVaccinations = PediatricVaccination::where('patient_id', $patient->id)->exists();
                $hasGrowth = PediatricGrowthRecord::where('patient_id', $patient->id)->exists();
                $hasAllergies = PediatricAllergy::where('patient_id', $patient->id)->exists();

                // Skip entirely if everything seeded for this patient
                if ($hasVaccinations && $hasGrowth) {
                    continue;
                }

                $doctor = ! empty($pediatricDoctors) ? $pediatricDoctors[array_rand($pediatricDoctors)] : null;
                $dob = $patient->date_of_birth ? Carbon::parse($patient->date_of_birth) : Carbon::now()->subYears(2);
                $ageMonths = (int) $dob->diffInMonths(Carbon::now());

                // ── Vaccinations ──
                // Use VACCINE_SCHEDULE — pick those already due for this child (months <= age), plus some scheduled ones
                if (! $hasVaccinations) {
                $schedule = PediatricVaccination::VACCINE_SCHEDULE;
                $dueVaccines      = array_filter($schedule, fn ($v) => $v['months'] <= $ageMonths);
                $upcomingVaccines = array_filter($schedule, fn ($v) => $v['months'] > $ageMonths);

                $numGiven = min(random_int(4, 6), count($dueVaccines));
                $numScheduled = min(random_int(1, 2), count($upcomingVaccines));

                // Shuffle and pick
                $givenPicks = [];
                if (! empty($dueVaccines)) {
                    $due = array_values($dueVaccines);
                    shuffle($due);
                    $givenPicks = array_slice($due, 0, $numGiven);
                }
                $schedPicks = [];
                if (! empty($upcomingVaccines)) {
                    $up = array_values($upcomingVaccines);
                    shuffle($up);
                    $schedPicks = array_slice($up, 0, $numScheduled);
                }

                foreach ($givenPicks as $v) {
                    $scheduledDate = $dob->copy()->addMonths($v['months']);
                    // Don't duplicate
                    $exists = PediatricVaccination::where('patient_id', $patient->id)
                        ->where('vaccine_name', $v['vaccine'])
                        ->where('dose_number', $v['dose'])
                        ->exists();
                    if ($exists) continue;

                    $givenDate = $scheduledDate->copy()->addDays(random_int(-3, 7));
                    if ($givenDate->isFuture()) $givenDate = Carbon::now()->subDays(random_int(1, 30));

                    PediatricVaccination::create([
                        'patient_id'        => $patient->id,
                        'doctor_id'         => $doctor?->id,
                        'vaccine_name'      => $v['vaccine'],
                        'vaccine_name_ar'   => $v['vaccine_ar'],
                        'dose_number'       => $v['dose'],
                        'scheduled_age'     => $v['age'],
                        'scheduled_date'    => $scheduledDate->toDateString(),
                        'given_date'        => $givenDate->toDateString(),
                        'batch_number'      => 'BT' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
                        'manufacturer'      => $manufacturers[array_rand($manufacturers)],
                        'site_of_injection' => $injectionSites[array_rand($injectionSites)],
                        'status'            => 'given',
                    ]);
                    $vaccineCount++;
                }

                foreach ($schedPicks as $v) {
                    $scheduledDate = $dob->copy()->addMonths($v['months']);
                    $exists = PediatricVaccination::where('patient_id', $patient->id)
                        ->where('vaccine_name', $v['vaccine'])
                        ->where('dose_number', $v['dose'])
                        ->exists();
                    if ($exists) continue;

                    PediatricVaccination::create([
                        'patient_id'      => $patient->id,
                        'doctor_id'       => $doctor?->id,
                        'vaccine_name'    => $v['vaccine'],
                        'vaccine_name_ar' => $v['vaccine_ar'],
                        'dose_number'     => $v['dose'],
                        'scheduled_age'   => $v['age'],
                        'scheduled_date'  => $scheduledDate->toDateString(),
                        'status'          => 'scheduled',
                    ]);
                    $vaccineCount++;
                }
                } // end if (! $hasVaccinations)

                // ── Growth Records ──
                // Benchmarks used to approximate values across age points
                if (! $hasGrowth) {
                $benchmarks = [
                    0  => ['w' => 3.5,  'h' => 50, 'hc' => 35],
                    1  => ['w' => 4.5,  'h' => 54, 'hc' => 37],
                    3  => ['w' => 6.0,  'h' => 60, 'hc' => 40],
                    6  => ['w' => 7.5,  'h' => 66, 'hc' => 43],
                    9  => ['w' => 8.5,  'h' => 71, 'hc' => 45],
                    12 => ['w' => 9.0,  'h' => 76, 'hc' => 46],
                    18 => ['w' => 10.5, 'h' => 81, 'hc' => 47.5],
                    24 => ['w' => 12.0, 'h' => 85, 'hc' => 49],
                    36 => ['w' => 14.0, 'h' => 95, 'hc' => 50],
                    48 => ['w' => 16.0, 'h' => 103, 'hc' => 50.5],
                ];

                $ageCheckpoints = [0, 1, 3, 6, 9, 12, 18, 24, 36, 48];
                $validCheckpoints = array_filter($ageCheckpoints, fn ($m) => $m <= $ageMonths);
                // Limit 5-8 records
                shuffle($validCheckpoints);
                $validCheckpoints = array_slice($validCheckpoints, 0, random_int(5, 8));
                sort($validCheckpoints);

                foreach ($validCheckpoints as $m) {
                    $bm = $benchmarks[$m] ?? $benchmarks[array_key_last($benchmarks)];
                    $measurementDate = $dob->copy()->addMonths($m);
                    if ($measurementDate->isFuture()) continue;

                    // Avoid duplicates
                    $exists = PediatricGrowthRecord::where('patient_id', $patient->id)
                        ->whereDate('measurement_date', $measurementDate->toDateString())
                        ->exists();
                    if ($exists) continue;

                    $weight = round($bm['w'] + (random_int(-5, 5) / 10), 2);
                    $height = round($bm['h'] + (random_int(-2, 2)), 1);
                    $hc     = round($bm['hc'] + (random_int(-5, 5) / 10), 1);
                    $bmi    = PediatricGrowthRecord::calculateBmi($weight, $height);

                    PediatricGrowthRecord::create([
                        'patient_id'            => $patient->id,
                        'doctor_id'             => $doctor?->id,
                        'measurement_date'      => $measurementDate->toDateString(),
                        'age_months'            => $m,
                        'weight_kg'             => $weight,
                        'height_cm'             => $height,
                        'head_circumference_cm' => $hc,
                        'bmi'                   => $bmi,
                    ]);
                    $growthCount++;
                }
                } // end if (! $hasGrowth)

                // ── Allergies (0-2) ──
                if (! $hasAllergies) {
                $allergyOptions = [
                    ['allergy_type' => 'food',        'allergen' => 'البيض',        'severity' => 'mild',     'symptoms' => ['rash', 'itching']],
                    ['allergy_type' => 'food',        'allergen' => 'الفول السوداني', 'severity' => 'severe',   'symptoms' => ['swelling', 'breathing_difficulty']],
                    ['allergy_type' => 'food',        'allergen' => 'الألبان',       'severity' => 'moderate', 'symptoms' => ['rash', 'vomiting']],
                    ['allergy_type' => 'food',        'allergen' => 'القمح',        'severity' => 'mild',     'symptoms' => ['stomach_pain']],
                    ['allergy_type' => 'drug',        'allergen' => 'Penicillin',    'severity' => 'severe',   'symptoms' => ['rash', 'swelling']],
                    ['allergy_type' => 'drug',        'allergen' => 'Aspirin',       'severity' => 'moderate', 'symptoms' => ['rash']],
                ];

                $numAllergies = random_int(0, 2);
                if ($numAllergies > 0) {
                    $keys = array_rand($allergyOptions, min($numAllergies, count($allergyOptions)));
                    $keys = is_array($keys) ? $keys : [$keys];

                    foreach ($keys as $k) {
                        $opt = $allergyOptions[$k];
                        $exists = PediatricAllergy::where('patient_id', $patient->id)
                            ->where('allergen', $opt['allergen'])
                            ->exists();
                        if ($exists) continue;

                        PediatricAllergy::create([
                            'patient_id'      => $patient->id,
                            'doctor_id'       => $doctor?->id,
                            'allergy_type'    => $opt['allergy_type'],
                            'allergen'        => $opt['allergen'],
                            'severity'        => $opt['severity'],
                            'symptoms'        => $opt['symptoms'],
                            'discovered_date' => Carbon::now()->subDays(random_int(30, 365))->toDateString(),
                            'is_active'       => true,
                            'notes'           => 'تم اكتشاف الحساسية أثناء الفحص الطبي.',
                        ]);
                        $allergyCount++;
                    }
                }
                } // end if (! $hasAllergies)
            }

            $this->command->info("    Created {$vaccineCount} vaccinations, {$growthCount} growth records, {$allergyCount} allergies.");
        } catch (\Throwable $e) {
            $this->command->error('    Failed to seed pediatric records: ' . $e->getMessage());
        }
    }
}

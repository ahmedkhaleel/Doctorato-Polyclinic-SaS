<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorServiceRate;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DentalSeeder extends Seeder
{
    /**
     * Seed dental service categories, services, and doctors.
     */
    public function run(): void
    {
        // ── Dental Service Categories ─────────────────────────────────
        $dentalCategories = [
            ['name_ar' => 'كشف الاسنان', 'name_en' => 'Dental Consultation', 'slug' => 'dental-consultation', 'display_order' => 20],
            ['name_ar' => 'تنظيف الاسنان', 'name_en' => 'Dental Cleaning', 'slug' => 'dental-cleaning', 'display_order' => 21],
            ['name_ar' => 'حشوات الاسنان', 'name_en' => 'Dental Fillings', 'slug' => 'dental-fillings', 'display_order' => 22],
            ['name_ar' => 'علاج الجذور', 'name_en' => 'Root Canal Treatment', 'slug' => 'root-canal', 'display_order' => 23],
            ['name_ar' => 'تقويم الاسنان', 'name_en' => 'Orthodontics', 'slug' => 'orthodontics', 'display_order' => 24],
            ['name_ar' => 'تجميل الاسنان', 'name_en' => 'Cosmetic Dentistry', 'slug' => 'cosmetic-dentistry', 'display_order' => 25],
            ['name_ar' => 'جراحة الفم', 'name_en' => 'Oral Surgery', 'slug' => 'oral-surgery', 'display_order' => 26],
            ['name_ar' => 'زراعة الاسنان', 'name_en' => 'Dental Implants', 'slug' => 'dental-implants', 'display_order' => 27],
        ];

        $categoryMap = [];
        foreach ($dentalCategories as $cat) {
            $created = ServiceCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
            $categoryMap[$cat['slug']] = $created->id;
        }

        // ── Dental Services ───────────────────────────────────────────
        $services = [
            // ── Dental Consultation ───────────────────────────────────
            [
                'category'               => 'dental-consultation',
                'name_ar'                => 'كشف اسنان',
                'name_en'                => 'Dental Check-up',
                'short_desc_ar'          => 'فحص شامل للاسنان واللثة مع تقديم خطة علاجية مناسبة.',
                'short_desc_en'          => 'Comprehensive dental and gum examination with a tailored treatment plan.',
                'price'                  => 200,
                'session_duration_minutes' => 30,
                'default_sessions'       => 1,
                'display_order'          => 1,
            ],
            [
                'category'               => 'dental-consultation',
                'name_ar'                => 'اشعة اسنان',
                'name_en'                => 'Dental X-Ray',
                'short_desc_ar'          => 'تصوير اشعة سينية للاسنان لتشخيص المشاكل الداخلية والتسوس.',
                'short_desc_en'          => 'Dental X-ray imaging for diagnosing internal problems and cavities.',
                'price'                  => 150,
                'session_duration_minutes' => 15,
                'default_sessions'       => 1,
                'display_order'          => 2,
            ],
            [
                'category'               => 'dental-consultation',
                'name_ar'                => 'اشعة بانوراما',
                'name_en'                => 'Panoramic X-Ray',
                'short_desc_ar'          => 'تصوير بانورامي شامل لجميع الاسنان والفكين لتقييم الحالة العامة.',
                'short_desc_en'          => 'Full panoramic imaging of all teeth and jaws for overall assessment.',
                'price'                  => 250,
                'session_duration_minutes' => 15,
                'default_sessions'       => 1,
                'display_order'          => 3,
            ],

            // ── Dental Cleaning ───────────────────────────────────────
            [
                'category'               => 'dental-cleaning',
                'name_ar'                => 'تنظيف الاسنان',
                'name_en'                => 'Dental Cleaning',
                'short_desc_ar'          => 'تنظيف وتلميع الاسنان وازالة الجير والترسبات للحفاظ على صحة اللثة.',
                'short_desc_en'          => 'Professional teeth cleaning and polishing to remove tartar and maintain gum health.',
                'price'                  => 300,
                'session_duration_minutes' => 30,
                'default_sessions'       => 1,
                'display_order'          => 4,
            ],
            [
                'category'               => 'dental-cleaning',
                'name_ar'                => 'تنظيف عميق',
                'name_en'                => 'Deep Cleaning',
                'short_desc_ar'          => 'تنظيف عميق تحت خط اللثة لعلاج التهابات اللثة وازالة الترسبات العميقة.',
                'short_desc_en'          => 'Deep cleaning below the gum line to treat gum disease and remove deep deposits.',
                'price'                  => 500,
                'session_duration_minutes' => 45,
                'default_sessions'       => 1,
                'display_order'          => 5,
            ],

            // ── Dental Fillings ───────────────────────────────────────
            [
                'category'               => 'dental-fillings',
                'name_ar'                => 'حشو تجميلي',
                'name_en'                => 'Composite Filling',
                'short_desc_ar'          => 'حشو تجميلي بلون الاسنان الطبيعي لعلاج التسوس واستعادة شكل السن.',
                'short_desc_en'          => 'Tooth-colored composite filling to treat cavities and restore tooth shape.',
                'price'                  => 400,
                'session_duration_minutes' => 30,
                'default_sessions'       => 1,
                'display_order'          => 6,
            ],
            [
                'category'               => 'dental-fillings',
                'name_ar'                => 'حشو عادي',
                'name_en'                => 'Amalgam Filling',
                'short_desc_ar'          => 'حشو معدني متين لعلاج التسوس في الاسنان الخلفية.',
                'short_desc_en'          => 'Durable amalgam filling for treating cavities in back teeth.',
                'price'                  => 250,
                'session_duration_minutes' => 30,
                'default_sessions'       => 1,
                'display_order'          => 7,
            ],

            // ── Root Canal Treatment ──────────────────────────────────
            [
                'category'               => 'root-canal',
                'name_ar'                => 'علاج جذور - اسنان امامية',
                'name_en'                => 'Root Canal - Front Tooth',
                'short_desc_ar'          => 'علاج عصب الاسنان الامامية لانقاذ السن من الخلع وازالة الالتهاب.',
                'short_desc_en'          => 'Root canal treatment for front teeth to save the tooth and eliminate infection.',
                'price'                  => 1500,
                'session_duration_minutes' => 60,
                'default_sessions'       => 1,
                'display_order'          => 8,
            ],
            [
                'category'               => 'root-canal',
                'name_ar'                => 'علاج جذور - ضرس',
                'name_en'                => 'Root Canal - Molar',
                'short_desc_ar'          => 'علاج عصب الضروس الخلفية متعددة القنوات لانقاذ الضرس والتخلص من الالم.',
                'short_desc_en'          => 'Multi-canal root canal treatment for molars to save the tooth and relieve pain.',
                'price'                  => 2500,
                'session_duration_minutes' => 90,
                'default_sessions'       => 2,
                'display_order'          => 9,
            ],

            // ── Orthodontics ──────────────────────────────────────────
            [
                'category'               => 'orthodontics',
                'name_ar'                => 'تقويم ثابت',
                'name_en'                => 'Fixed Orthodontics',
                'short_desc_ar'          => 'تقويم اسنان ثابت معدني او سيراميك لتصحيح اعوجاج الاسنان وتحسين الاطباق.',
                'short_desc_en'          => 'Fixed metal or ceramic braces to correct teeth alignment and improve bite.',
                'price'                  => 15000,
                'session_duration_minutes' => 45,
                'default_sessions'       => 18,
                'display_order'          => 10,
            ],
            [
                'category'               => 'orthodontics',
                'name_ar'                => 'تقويم شفاف',
                'name_en'                => 'Clear Aligners',
                'short_desc_ar'          => 'تقويم شفاف متحرك غير مرئي لتصحيح الاسنان بشكل مريح وجمالي.',
                'short_desc_en'          => 'Invisible clear aligners for comfortable and aesthetic teeth straightening.',
                'price'                  => 20000,
                'session_duration_minutes' => 30,
                'default_sessions'       => 12,
                'display_order'          => 11,
            ],

            // ── Cosmetic Dentistry ────────────────────────────────────
            [
                'category'               => 'cosmetic-dentistry',
                'name_ar'                => 'تبييض الاسنان',
                'name_en'                => 'Teeth Whitening',
                'short_desc_ar'          => 'تبييض الاسنان بتقنية الليزر للحصول على ابتسامة ناصعة البياض.',
                'short_desc_en'          => 'Laser teeth whitening for a bright, white smile.',
                'price'                  => 1500,
                'session_duration_minutes' => 60,
                'default_sessions'       => 1,
                'display_order'          => 12,
            ],
            [
                'category'               => 'cosmetic-dentistry',
                'name_ar'                => 'فينير للسن الواحد',
                'name_en'                => 'Veneer per Tooth',
                'short_desc_ar'          => 'قشور خزفية رقيقة لتحسين مظهر الاسنان ومعالجة التشققات والتصبغات.',
                'short_desc_en'          => 'Thin porcelain veneers to enhance tooth appearance and treat cracks and stains.',
                'price'                  => 3000,
                'session_duration_minutes' => 45,
                'default_sessions'       => 2,
                'display_order'          => 13,
            ],
            [
                'category'               => 'cosmetic-dentistry',
                'name_ar'                => 'تاج زركونيا',
                'name_en'                => 'Zirconia Crown',
                'short_desc_ar'          => 'تاج من الزركونيا عالي المتانة والجمال لتغطية وحماية الاسنان التالفة.',
                'short_desc_en'          => 'High-strength zirconia crown for protecting and restoring damaged teeth.',
                'price'                  => 3500,
                'session_duration_minutes' => 45,
                'default_sessions'       => 2,
                'display_order'          => 14,
            ],
            [
                'category'               => 'cosmetic-dentistry',
                'name_ar'                => 'تاج بورسلين',
                'name_en'                => 'Porcelain Crown',
                'short_desc_ar'          => 'تاج من البورسلين بمظهر طبيعي لاستعادة شكل ووظيفة السن.',
                'short_desc_en'          => 'Natural-looking porcelain crown to restore tooth shape and function.',
                'price'                  => 2500,
                'session_duration_minutes' => 45,
                'default_sessions'       => 2,
                'display_order'          => 15,
            ],

            // ── Oral Surgery ──────────────────────────────────────────
            [
                'category'               => 'oral-surgery',
                'name_ar'                => 'خلع سن بسيط',
                'name_en'                => 'Tooth Extraction - Simple',
                'short_desc_ar'          => 'خلع سن بسيط بتخدير موضعي بدون جراحة.',
                'short_desc_en'          => 'Simple tooth extraction under local anesthesia without surgery.',
                'price'                  => 300,
                'session_duration_minutes' => 30,
                'default_sessions'       => 1,
                'display_order'          => 16,
            ],
            [
                'category'               => 'oral-surgery',
                'name_ar'                => 'خلع جراحي',
                'name_en'                => 'Tooth Extraction - Surgical',
                'short_desc_ar'          => 'خلع جراحي للاسنان المكسورة او المدفونة جزئيا.',
                'short_desc_en'          => 'Surgical extraction for broken or partially impacted teeth.',
                'price'                  => 800,
                'session_duration_minutes' => 45,
                'default_sessions'       => 1,
                'display_order'          => 17,
            ],
            [
                'category'               => 'oral-surgery',
                'name_ar'                => 'خلع ضرس العقل',
                'name_en'                => 'Wisdom Tooth Extraction',
                'short_desc_ar'          => 'خلع ضرس العقل المدفون او المائل جراحيا بأمان وكفاءة.',
                'short_desc_en'          => 'Safe and efficient surgical extraction of impacted or misaligned wisdom teeth.',
                'price'                  => 1200,
                'session_duration_minutes' => 60,
                'default_sessions'       => 1,
                'display_order'          => 18,
            ],

            // ── Dental Implants ───────────────────────────────────────
            [
                'category'               => 'dental-implants',
                'name_ar'                => 'زراعة سن',
                'name_en'                => 'Dental Implant',
                'short_desc_ar'          => 'زراعة سن تعويضي من التيتانيوم لاستبدال الاسنان المفقودة بشكل دائم.',
                'short_desc_en'          => 'Titanium dental implant for permanent replacement of missing teeth.',
                'price'                  => 8000,
                'session_duration_minutes' => 90,
                'default_sessions'       => 3,
                'display_order'          => 19,
            ],
        ];

        foreach ($services as $service) {
            $categorySlug = $service['category'];
            unset($service['category']);

            $slug = Str::slug($service['name_en']);

            Service::updateOrCreate(
                ['slug' => $slug],
                array_merge($service, [
                    'category_id'     => $categoryMap[$categorySlug],
                    'slug'            => $slug,
                    'module'          => 'dental',
                    'bookable'        => true,
                    'status'          => 'active',
                    'show_on_website' => true,
                    'show_on_home'    => false,
                ])
            );
        }

        // ── Dental Doctors ────────────────────────────────────────────
        $doctorRole = Role::where('name', 'doctor')->first();

        // Resolve all dental service IDs for rate assignment
        $dentalServiceIds = Service::where('module', 'dental')->pluck('id')->toArray();

        // ── Dr Ahmed Khalil ───────────────────────────────────────────
        $userAhmed = User::firstOrCreate(
            ['email' => 'dr.ahmed.dental@auraderma.com'],
            [
                'name'      => 'Dr Ahmed Khalil',
                'password'  => Hash::make('password'),
                'role_id'   => $doctorRole->id,
                'is_active' => true,
            ]
        );

        $doctorAhmed = Doctor::updateOrCreate(
            ['name_en' => 'Dr Ahmed Khalil'],
            [
                'name_ar'                        => 'د. أحمد خليل',
                'specialization_ar'              => 'استشاري طب وجراحة الفم والاسنان',
                'specialization_en'              => 'Dental Consultant',
                'doctor_type'                    => 'consultant',
                'module'                         => 'dental',
                'status'                         => 'active',
                'display_order'                  => 10,
                'default_commission_percentage'   => 30.00,
                'dental_consultation_fee'        => 0,
                'dental_service_fee'             => 0,
                'dental_consultation_commission'  => 50.00,
                'dental_service_commission'       => 30.00,
                'user_id'                        => $userAhmed->id,
            ]
        );

        // Weekly schedule: Sunday 10:00 AM - 2:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorAhmed->id, 'day_of_week' => 1], // 1 = Sunday
            [
                'start_time' => '10:00:00',
                'end_time'   => '14:00:00',
                'is_active'  => true,
            ]
        );

        // Weekly schedule: Wednesday 10:00 AM - 2:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorAhmed->id, 'day_of_week' => 4], // 4 = Wednesday
            [
                'start_time' => '10:00:00',
                'end_time'   => '14:00:00',
                'is_active'  => true,
            ]
        );

        // Service Rates (Consultant: 30% on all dental services)
        $this->seedServiceRates($doctorAhmed->id, $dentalServiceIds, 30.00);

        // ── Dr Sara Mohamed ───────────────────────────────────────────
        $userSara = User::firstOrCreate(
            ['email' => 'dr.sara.dental@auraderma.com'],
            [
                'name'      => 'Dr Sara Mohamed',
                'password'  => Hash::make('password'),
                'role_id'   => $doctorRole->id,
                'is_active' => true,
            ]
        );

        $doctorSara = Doctor::updateOrCreate(
            ['name_en' => 'Dr Sara Mohamed'],
            [
                'name_ar'                        => 'د. سارة محمد',
                'specialization_ar'              => 'أخصائي طب الاسنان',
                'specialization_en'              => 'Dental Specialist',
                'doctor_type'                    => 'specialist',
                'module'                         => 'dental',
                'status'                         => 'active',
                'display_order'                  => 11,
                'default_commission_percentage'   => 20.00,
                'dental_consultation_fee'        => 0,
                'dental_service_fee'             => 0,
                'dental_consultation_commission'  => 40.00,
                'dental_service_commission'       => 20.00,
                'user_id'                        => $userSara->id,
            ]
        );

        // Weekly schedule: Saturday 2:00 PM - 8:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorSara->id, 'day_of_week' => 0], // 0 = Saturday
            [
                'start_time' => '14:00:00',
                'end_time'   => '20:00:00',
                'is_active'  => true,
            ]
        );

        // Weekly schedule: Tuesday 2:00 PM - 8:00 PM
        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctorSara->id, 'day_of_week' => 3], // 3 = Tuesday
            [
                'start_time' => '14:00:00',
                'end_time'   => '20:00:00',
                'is_active'  => true,
            ]
        );

        // Service Rates (Specialist: 20% on all dental services)
        $this->seedServiceRates($doctorSara->id, $dentalServiceIds, 20.00);
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

<?php

namespace Tests\Feature\Secretary;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $secretary;

    protected Patient $patient;

    protected Doctor $doctor;

    protected Service $service;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            [
                'display_name_en' => 'Secretary',
                'display_name_ar' => 'سكرتير',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        $this->secretary = User::create([
            'name' => 'Secretary',
            'email' => 'secretary@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01234567890']);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور أحمد',
            'name_en' => 'Dr. Ahmed',
            'status' => 'active',
            'dermatology_fee' => 200,
            'cosmetic_fee' => 300,
        ]);

        DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => now()->addDay()->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $category = ServiceCategory::create([
            'name_ar' => 'تصنيف',
            'name_en' => 'Category',
            'slug' => 'category',
        ]);

        $this->service = Service::create([
            'category_id' => $category->id,
            'name_ar' => 'خدمة',
            'name_en' => 'Service',
            'slug' => 'service',
            'status' => 'active',
            'bookable' => true,
            'show_on_website' => true,
            'price' => 500,
            'default_sessions' => 1,
            'session_duration_minutes' => 30,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name_ar' => 'كاش',
            'name_en' => 'Cash',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Setting::set('default_dermatology_fee', 150);
        Setting::set('default_cosmetic_fee', 250);
    }

    public function test_secretary_can_view_bookings_index(): void
    {
        $this->actingAs($this->secretary);
        $response = $this->get('/secretary/bookings');
        $response->assertStatus(200);
    }

    public function test_secretary_can_view_create_booking_page(): void
    {
        $this->actingAs($this->secretary);
        $response = $this->get('/secretary/bookings/create');
        $response->assertStatus(200);
    }

    public function test_secretary_can_create_service_booking(): void
    {
        $this->actingAs($this->secretary);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/secretary/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 500,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '10:00',
                            'end_time' => '10:30',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success'); // message is locale-aware (ar/en)

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'service',
            'status' => 'confirmed',
        ]);
    }

    public function test_secretary_can_create_dermatology_consultation(): void
    {
        $this->actingAs($this->secretary);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/secretary/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'dermatology_consultation',
            'services' => [
                [
                    'service_id' => null,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 200,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '11:00',
                            'end_time' => '11:30',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', ['booking_type' => 'dermatology_consultation']);
    }

    public function test_secretary_can_create_cosmetic_consultation(): void
    {
        $this->actingAs($this->secretary);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/secretary/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'cosmetic_consultation',
            'services' => [
                [
                    'service_id' => null,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 300,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '12:00',
                            'end_time' => '12:30',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['booking_type' => 'cosmetic_consultation']);
    }

    public function test_secretary_can_create_psychiatry_consultation(): void
    {
        $this->actingAs($this->secretary);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/secretary/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'psychiatry_consultation',
            'services' => [
                [
                    'service_id' => null,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 300,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '13:00',
                            'end_time' => '13:30',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        // booking_type is accepted by StoreBookingRequest and the module is
        // auto-detected as psychiatry.
        $this->assertDatabaseHas('bookings', ['booking_type' => 'psychiatry_consultation', 'module' => 'psychiatry']);
    }

    public function test_secretary_can_view_booking_details(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-202602-0001',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Test',
            'phone' => '012345',
            'booking_type' => 'service',
        ]);

        $this->actingAs($this->secretary);
        $response = $this->get("/secretary/bookings/{$booking->id}");
        $response->assertStatus(200);
    }

    public function test_secretary_can_confirm_booking(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-202602-0005',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Website User',
            'phone' => '0111222333',
            'booking_type' => 'service',
        ]);

        $this->actingAs($this->secretary);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post("/secretary/bookings/{$booking->id}/confirm", [
            'patient_id' => $this->patient->id,
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 500,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '14:00',
                            'end_time' => '14:30',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_secretary_can_process_payment(): void
    {
        $this->actingAs($this->secretary);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->post('/secretary/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 500,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '10:00',
                            'end_time' => '10:30',
                        ],
                    ],
                ],
            ],
        ]);

        $booking = Booking::latest()->first();

        $response = $this->post("/secretary/bookings/{$booking->id}/payment", [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Patient $patient;
    protected Doctor $doctor;
    protected Service $service;
    protected ServiceCategory $category;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Admin',
                'display_name_ar' => 'مدير',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
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

        $this->category = ServiceCategory::create([
            'name_ar' => 'تصنيف',
            'name_en' => 'Category',
            'slug' => 'category',
        ]);

        $this->service = Service::create([
            'category_id' => $this->category->id,
            'name_ar' => 'خدمة تجريبية',
            'name_en' => 'Test Service',
            'slug' => 'test-service',
            'status' => 'active',
            'show_on_website' => true,
            'bookable' => true,
            'price' => 500,
            'default_sessions' => 3,
            'session_duration_minutes' => 30,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name_ar' => 'كاش',
            'name_en' => 'Cash',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Setting::set('default_dermatology_fee', 150, 'consultation');
        Setting::set('default_cosmetic_fee', 250, 'consultation');
    }

    // ─── Bookings Index ────────────────────────────────

    public function test_admin_can_view_bookings_index(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/bookings');
        $response->assertStatus(200);
    }

    public function test_admin_bookings_index_shows_bookings(): void
    {
        Booking::create([
            'booking_number' => 'BK-202602-0001',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Test Person',
            'phone' => '0123456',
            'booking_type' => 'service',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get('/admin/bookings');
        $response->assertStatus(200);
    }

    public function test_admin_bookings_can_filter_by_status(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/bookings?status=confirmed');
        $response->assertStatus(200);
    }

    public function test_admin_bookings_can_filter_by_source(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/bookings?source=website');
        $response->assertStatus(200);
    }

    public function test_admin_bookings_can_search(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/bookings?search=test');
        $response->assertStatus(200);
    }

    // ─── Bookings Create ───────────────────────────────

    public function test_admin_can_view_create_booking_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/bookings/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_service_booking(): void
    {
        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/admin/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'service',
            'notes' => 'Test booking',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 500,
                    'discount_per_session' => 0,
                    'notes' => '',
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
        $response->assertSessionHas('success', 'Booking created successfully.');

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'service',
            'status' => 'confirmed',
            'source' => 'admin',
        ]);

        $this->assertDatabaseHas('booking_services', [
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'unit_price' => 500,
        ]);

        $this->assertDatabaseHas('booking_appointments', [
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $tomorrow,
            'start_time' => '10:00',
            'status' => 'scheduled',
        ]);

        // Should have invoice
        $booking = Booking::latest()->first();
        $this->assertNotNull($booking->invoice_id);
    }

    public function test_admin_can_create_dermatology_consultation_booking(): void
    {
        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/admin/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'dermatology_consultation',
            'notes' => 'Derm consultation',
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

        $this->assertDatabaseHas('bookings', [
            'booking_type' => 'dermatology_consultation',
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('booking_services', [
            'service_id' => null,
            'doctor_id' => $this->doctor->id,
            'unit_price' => 200,
            'sessions_count' => 1,
        ]);
    }

    public function test_admin_can_create_cosmetic_consultation_booking(): void
    {
        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/admin/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'cosmetic_consultation',
            'notes' => 'Cosmetic consultation',
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
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'booking_type' => 'cosmetic_consultation',
        ]);
    }

    public function test_service_booking_requires_service_id(): void
    {
        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/admin/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => null,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 500,
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

        $response->assertSessionHasErrors('services.0.service_id');
    }

    public function test_consultation_booking_does_not_require_service_id(): void
    {
        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post('/admin/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'dermatology_consultation',
            'services' => [
                [
                    'service_id' => null,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 150,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '09:00',
                            'end_time' => '09:30',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors('services.0.service_id');
    }

    public function test_booking_requires_patient_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/bookings', [
            'booking_type' => 'service',
            'services' => [],
        ]);

        $response->assertSessionHasErrors('patient_id');
    }

    public function test_booking_requires_valid_booking_type(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/bookings', [
            'patient_id' => $this->patient->id,
            'booking_type' => 'invalid_type',
            'services' => [],
        ]);

        $response->assertSessionHasErrors('booking_type');
    }

    // ─── Bookings Show ─────────────────────────────────

    public function test_admin_can_view_booking_details(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-202602-0001',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Test Person',
            'phone' => '0123456',
            'booking_type' => 'service',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get("/admin/bookings/{$booking->id}");
        $response->assertStatus(200);
    }

    // ─── Bookings Update ───────────────────────────────

    public function test_admin_can_update_booking_status(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-202602-0001',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Test Person',
            'phone' => '0123456',
            'booking_type' => 'service',
        ]);

        $this->actingAs($this->admin);

        $response = $this->patch("/admin/bookings/{$booking->id}", [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    // ─── Bookings Confirm ──────────────────────────────

    public function test_admin_can_confirm_unconfirmed_booking(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-202602-0002',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Website User',
            'phone' => '0111222333',
            'booking_type' => 'service',
        ]);

        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post("/admin/bookings/{$booking->id}/confirm", [
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
        $response->assertSessionHas('success', 'Booking confirmed successfully.');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_admin_cannot_confirm_already_confirmed_booking(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-202602-0003',
            'source' => 'website',
            'status' => 'confirmed',
            'full_name' => 'Already Confirmed',
            'phone' => '0111222333',
            'booking_type' => 'service',
            'patient_id' => $this->patient->id,
        ]);

        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post("/admin/bookings/{$booking->id}/confirm", [
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

        $response->assertSessionHas('error', 'This booking is already confirmed.');
    }

    public function test_admin_can_confirm_consultation_booking_without_service(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-202602-0004',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Consultation Patient',
            'phone' => '0111333444',
            'booking_type' => 'dermatology_consultation',
        ]);

        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post("/admin/bookings/{$booking->id}/confirm", [
            'patient_id' => $this->patient->id,
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
                            'start_time' => '15:00',
                            'end_time' => '15:30',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'booking_type' => 'dermatology_consultation',
        ]);

        $this->assertDatabaseHas('booking_services', [
            'booking_id' => $booking->id,
            'service_id' => null,
            'unit_price' => 200,
        ]);
    }

    // ─── Payment ───────────────────────────────────────

    public function test_admin_can_process_payment(): void
    {
        // Create a confirmed booking with invoice
        $this->actingAs($this->admin);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->post('/admin/bookings', [
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

        $response = $this->post("/admin/bookings/{$booking->id}/payment", [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
            'reference_number' => null,
            'notes' => 'Full payment',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Invoice should be paid
        $invoice = Invoice::where('booking_id', $booking->id)->first();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(500, (float) $invoice->paid_amount);
    }

    // ─── Export ─────────────────────────────────────────

    public function test_admin_can_export_bookings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/bookings/export');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}

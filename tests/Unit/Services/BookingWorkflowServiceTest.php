<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\BookingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Patient $patient;
    protected Doctor $doctor;
    protected Service $service;
    protected PaymentMethod $paymentMethod;
    protected BookingWorkflowService $workflowService;

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
            'name' => 'Admin',
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
            'name_ar' => 'دكتور',
            'name_en' => 'Dr. Test',
            'status' => 'active',
            'dermatology_fee' => 200,
            'cosmetic_fee' => 300,
            'default_commission_percentage' => 30,
        ]);

        $category = ServiceCategory::create([
            'name_ar' => 'تصنيف',
            'name_en' => 'Category',
            'slug' => 'test-cat',
        ]);

        $this->service = Service::create([
            'category_id' => $category->id,
            'name_ar' => 'ليزر',
            'name_en' => 'Laser Treatment',
            'slug' => 'laser-treatment',
            'status' => 'active',
            'bookable' => true,
            'show_on_website' => true,
            'price' => 1000,
            'default_sessions' => 3,
            'session_duration_minutes' => 45,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name_ar' => 'كاش',
            'name_en' => 'Cash',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Setting::set('default_dermatology_fee', 150);
        Setting::set('default_cosmetic_fee', 250);

        $this->actingAs($this->admin);

        $this->workflowService = app(BookingWorkflowService::class);
    }

    // ═══════════════════════════════════════════════════
    // createFromWebsite
    // ═══════════════════════════════════════════════════

    public function test_create_from_website_creates_unconfirmed_booking(): void
    {
        $booking = $this->workflowService->createFromWebsite([
            'full_name' => 'Web Customer',
            'phone' => '01000000001',
            'email' => 'web@test.com',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'preferred_date' => now()->addDays(3)->format('Y-m-d'),
            'preferred_time' => '10:00',
            'notes' => 'From website',
        ]);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals('unconfirmed', $booking->status);
        $this->assertEquals('website', $booking->source);
        $this->assertNotNull($booking->booking_number);
        $this->assertStringStartsWith('BK-', $booking->booking_number);
        $this->assertEquals('Web Customer', $booking->full_name);
        $this->assertEquals('01000000001', $booking->phone);
        $this->assertEquals('web@test.com', $booking->email);
        $this->assertEquals($this->service->id, $booking->service_id);
        $this->assertEquals($this->doctor->id, $booking->doctor_id);
    }

    public function test_create_from_website_detects_dental_module(): void
    {
        $booking = $this->workflowService->createFromWebsite([
            'full_name' => 'Dental Customer',
            'phone' => '01000000002',
            'booking_type' => 'dental_consultation',
        ]);

        $this->assertEquals('dental', $booking->module);
    }

    public function test_create_from_website_defaults_to_derma_module(): void
    {
        $booking = $this->workflowService->createFromWebsite([
            'full_name' => 'Derma Customer',
            'phone' => '01000000003',
            'booking_type' => 'service',
        ]);

        $this->assertEquals('derma', $booking->module);
    }

    // ═══════════════════════════════════════════════════
    // createFromSecretary
    // ═══════════════════════════════════════════════════

    public function test_create_from_secretary_creates_confirmed_booking(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
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
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('secretary', $booking->source);
        $this->assertEquals($this->admin->id, $booking->created_by);
        $this->assertEquals($this->patient->id, $booking->patient_id);
    }

    public function test_create_from_secretary_creates_booking_services(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 3,
                    'unit_price' => 1000,
                    'discount_per_session' => 100,
                    'notes' => 'VIP discount',
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => now()->addDays(7)->format('Y-m-d'),
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => now()->addDays(14)->format('Y-m-d'),
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];
        $this->assertCount(1, $booking->bookingServices);

        $bs = $booking->bookingServices->first();
        $this->assertEquals($this->service->id, $bs->service_id);
        $this->assertEquals($this->doctor->id, $bs->doctor_id);
        $this->assertEquals(3, $bs->sessions_count);
        $this->assertEquals(1000, (float) $bs->unit_price);
        $this->assertEquals(100, (float) $bs->discount_per_session);
        // total_price = (1000 - 100) * 3 = 2700
        $this->assertEquals(2700, (float) $bs->total_price);
        $this->assertEquals('pending', $bs->status);
    }

    public function test_create_from_secretary_creates_booking_appointments(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');
        $nextWeek = now()->addDays(7)->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 2,
                    'unit_price' => 500,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $nextWeek,
                            'start_time' => '14:00',
                            'end_time' => '14:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];
        $this->assertCount(2, $booking->appointments);

        $firstAppt = $booking->appointments->sortBy('session_number')->first();
        $this->assertEquals(1, $firstAppt->session_number);
        $this->assertEquals('scheduled', $firstAppt->status);
        $this->assertEquals($this->doctor->id, $firstAppt->doctor_id);
        $this->assertEquals($tomorrow, $firstAppt->appointment_date->format('Y-m-d'));
        $this->assertStringStartsWith('10:00', $firstAppt->start_time);
    }

    public function test_create_from_secretary_generates_invoice_with_correct_total(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 2,
                    'unit_price' => 1000,
                    'discount_per_session' => 50,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => now()->addDays(7)->format('Y-m-d'),
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $invoice = $result['invoice'];
        // total = (1000 - 50) * 2 = 1900
        $this->assertEquals(1900, (float) $invoice->total);
        $this->assertEquals(1900, (float) $invoice->subtotal);
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals($this->patient->id, $invoice->patient_id);
        $this->assertEquals($this->admin->id, $invoice->created_by);
        $this->assertNotNull($invoice->invoice_number);
    }

    public function test_create_from_secretary_creates_invoice_items(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 2,
                    'unit_price' => 1000,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => now()->addDays(7)->format('Y-m-d'),
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $invoice = $result['invoice'];
        $items = $invoice->items;
        $this->assertCount(1, $items);

        $item = $items->first();
        $this->assertStringContainsString('Laser Treatment', $item->description_en);
        $this->assertStringContainsString('2 sessions', $item->description_en);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(1000, (float) $item->unit_price);
        $this->assertEquals(2000, (float) $item->total);
    }

    // ═══════════════════════════════════════════════════
    // processPayment
    // ═══════════════════════════════════════════════════

    public function test_process_payment_records_payment_and_updates_invoice(): void
    {
        $today = now()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 800,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $today,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];

        $paymentResult = $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 800,
        ], $this->admin->id);

        // Payment recorded
        $this->assertInstanceOf(Payment::class, $paymentResult['payment']);
        $this->assertEquals(800, (float) $paymentResult['payment']->amount);
        $this->assertEquals($this->paymentMethod->id, $paymentResult['payment']->payment_method_id);
        $this->assertEquals($this->patient->id, $paymentResult['payment']->patient_id);

        // Invoice fully paid
        $this->assertEquals('paid', $paymentResult['invoice']->status);
        $this->assertEquals(800, (float) $paymentResult['invoice']->paid_amount);

        // Booking moved to in_progress
        $booking->refresh();
        $this->assertEquals('in_progress', $booking->status);
    }

    // ═══════════════════════════════════════════════════
    // generateBookingInvoice
    // ═══════════════════════════════════════════════════

    public function test_generate_booking_invoice_matches_services_total(): void
    {
        // Create a booking with booking services manually
        $booking = new Booking([
            'booking_number' => Booking::generateBookingNumber(),
            'source' => 'secretary',
            'module' => 'derma',
            'booking_type' => 'service',
            'status' => 'confirmed',
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
        ]);
        $booking->created_by = $this->admin->id;
        $booking->save();

        BookingService::create([
            'booking_id' => $booking->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 2,
            'unit_price' => 500,
            'discount_per_session' => 0,
            'total_price' => 1000,
            'status' => 'pending',
        ]);

        $secondCategory = ServiceCategory::create([
            'name_ar' => 'تصنيف2',
            'name_en' => 'Category2',
            'slug' => 'cat2',
        ]);
        $secondService = Service::create([
            'category_id' => $secondCategory->id,
            'name_ar' => 'فيلر',
            'name_en' => 'Filler',
            'slug' => 'filler',
            'status' => 'active',
            'bookable' => true,
            'show_on_website' => true,
            'price' => 600,
        ]);

        BookingService::create([
            'booking_id' => $booking->id,
            'service_id' => $secondService->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 1,
            'unit_price' => 600,
            'discount_per_session' => 0,
            'total_price' => 600,
            'status' => 'pending',
        ]);

        $invoice = $this->workflowService->generateBookingInvoice($booking, $this->admin->id);

        // Total should be 1000 + 600 = 1600
        $this->assertEquals(1600, (float) $invoice->total);
        $this->assertEquals(1600, (float) $invoice->subtotal);
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals('unpaid', $invoice->status);

        // Two invoice items
        $this->assertCount(2, $invoice->items);
    }

    // ═══════════════════════════════════════════════════
    // handleVisitCompleted
    // ═══════════════════════════════════════════════════

    public function test_handle_visit_completed_marks_appointment_completed(): void
    {
        $today = now()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
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
                            'date' => $today,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];

        $paymentResult = $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
        ], $this->admin->id);

        $visit = $paymentResult['visits_created'][0];

        $this->workflowService->handleVisitCompleted($visit);

        // Appointment marked completed
        $appointment = BookingAppointment::where('visit_id', $visit->id)->first();
        $this->assertEquals('completed', $appointment->status);

        // BookingService completed_sessions incremented
        $bs = BookingService::where('booking_id', $booking->id)->first();
        $this->assertEquals(1, $bs->completed_sessions);
        $this->assertEquals('completed', $bs->status);

        // Booking marked completed (single service, single session)
        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
    }

    public function test_handle_visit_completed_does_not_complete_booking_with_remaining_sessions(): void
    {
        $today = now()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'service',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 2,
                    'unit_price' => 500,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $today,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => now()->addDays(7)->format('Y-m-d'),
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];

        $paymentResult = $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 1000,
        ], $this->admin->id);

        $visit = $paymentResult['visits_created'][0];

        $this->workflowService->handleVisitCompleted($visit);

        // BookingService has 1 completed out of 2
        $bs = BookingService::where('booking_id', $booking->id)->first();
        $this->assertEquals(1, $bs->completed_sessions);
        $this->assertNotEquals('completed', $bs->status);

        // Booking should NOT be completed yet
        $booking->refresh();
        $this->assertNotEquals('completed', $booking->status);
    }

    // ═══════════════════════════════════════════════════
    // handleVisitCancelled
    // ═══════════════════════════════════════════════════

    public function test_handle_visit_cancelled_reverts_appointment_and_sessions(): void
    {
        $today = now()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
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
                            'date' => $today,
                            'start_time' => '10:00',
                            'end_time' => '10:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];

        $paymentResult = $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
        ], $this->admin->id);

        $visit = $paymentResult['visits_created'][0];

        // Complete, then cancel
        $this->workflowService->handleVisitCompleted($visit);

        $visit->status = 'cancelled';
        $visit->save();
        $this->workflowService->handleVisitCancelled($visit);

        // Appointment reverted to scheduled, visit_id cleared
        $appointment = BookingAppointment::where('booking_id', $booking->id)->first();
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertNull($appointment->visit_id);

        // completed_sessions decremented back to 0
        $bs = BookingService::where('booking_id', $booking->id)->first();
        $this->assertEquals(0, $bs->completed_sessions);
    }

    // ═══════════════════════════════════════════════════
    // checkFollowUpEligibility
    // ═══════════════════════════════════════════════════

    public function test_check_follow_up_eligibility_returns_null_when_no_recent_visits(): void
    {
        Setting::set('followup_window_days', 15);
        Setting::set('followup_fee', 50);

        $result = $this->workflowService->checkFollowUpEligibility($this->patient->id);

        $this->assertNull($result);
    }

    public function test_check_follow_up_eligibility_returns_data_when_recent_derma_visit_exists(): void
    {
        Setting::set('followup_window_days', 15);
        Setting::set('followup_fee', 50);

        // Create a booking for the visit (visit requires booking_id)
        $booking = Booking::create([
            'booking_number' => Booking::generateBookingNumber(),
            'source' => 'secretary',
            'module' => 'derma',
            'booking_type' => 'dermatology_consultation',
            'status' => 'completed',
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
        ]);

        // Create a completed dermatology consultation visit
        Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'receptionist_id' => $this->admin->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'completed',
            'visit_date' => now()->subDays(5),
            'scheduled_time' => '10:00',
        ]);

        $result = $this->workflowService->checkFollowUpEligibility($this->patient->id);

        $this->assertNotNull($result);
        $this->assertTrue($result['eligible']);
        $this->assertEquals(50, $result['follow_up_fee']);
        $this->assertEquals(15, $result['window_days']);
        $this->assertArrayHasKey('original_visit_date', $result);
        $this->assertArrayHasKey('original_visit_id', $result);
    }
}

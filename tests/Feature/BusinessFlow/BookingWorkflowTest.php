<?php

namespace Tests\Feature\BusinessFlow;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
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
use App\Services\TimeSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
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

        $this->patient = new Patient(['full_name' => 'Workflow Patient', 'phone' => '01234567890']);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور',
            'name_en' => 'Dr. Workflow',
            'status' => 'active',
            'dermatology_fee' => 200,
            'cosmetic_fee' => 300,
            'default_commission_percentage' => 30,
        ]);

        $category = ServiceCategory::create([
            'name_ar' => 'تصنيف',
            'name_en' => 'Category',
            'slug' => 'cat',
        ]);

        $this->service = Service::create([
            'category_id' => $category->id,
            'name_ar' => 'ليزر',
            'name_en' => 'Laser Treatment',
            'slug' => 'laser',
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

        $this->workflowService = app(BookingWorkflowService::class);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 1: Website Booking → Admin Confirm → Payment → Visit
    // ═══════════════════════════════════════════════════

    public function test_full_website_service_booking_flow(): void
    {
        // Step 1: Customer submits booking from website
        $booking = $this->workflowService->createFromWebsite([
            'full_name' => 'Website Customer',
            'phone' => '01000000000',
            'email' => 'customer@test.com',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'preferred_date' => now()->addDays(2)->format('Y-m-d'),
            'preferred_time' => '10:00',
            'notes' => 'I want laser treatment',
        ]);

        $this->assertEquals('unconfirmed', $booking->status);
        $this->assertEquals('website', $booking->source);
        $this->assertNotNull($booking->booking_number);

        // Step 2: Admin confirms the booking
        $tomorrow = now()->addDay()->format('Y-m-d');
        $result = $this->workflowService->confirmWebsiteBooking($booking, [
            'patient_id' => $this->patient->id,
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 3,
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

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals($this->patient->id, $booking->patient_id);

        // Booking services created
        $this->assertCount(1, $booking->bookingServices);
        $bs = $booking->bookingServices->first();
        $this->assertEquals($this->service->id, $bs->service_id);
        $this->assertEquals(3, $bs->sessions_count);
        $this->assertEquals(3000, (float) $bs->total_price);

        // Appointments created
        $this->assertCount(3, $booking->appointments);

        // Invoice created
        $this->assertNotNull($booking->invoice_id);
        $invoice = Invoice::find($booking->invoice_id);
        $this->assertEquals(3000, (float) $invoice->total);
        $this->assertEquals('unpaid', $invoice->status);

        // Invoice items
        $this->assertCount(1, $invoice->items);
        $item = $invoice->items->first();
        $this->assertStringContainsString('Laser Treatment', $item->description_en);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 2: Admin Direct Create → Service Booking
    // ═══════════════════════════════════════════════════

    public function test_full_admin_direct_service_booking_flow(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'service',
            'notes' => 'Direct booking',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 2,
                    'unit_price' => 1000,
                    'discount_per_session' => 100,
                    'notes' => 'VIP discount',
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '14:00',
                            'end_time' => '14:45',
                        ],
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => now()->addDays(7)->format('Y-m-d'),
                            'start_time' => '14:00',
                            'end_time' => '14:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('secretary', $booking->source);

        // Discount applied
        $bs = $booking->bookingServices->first();
        $this->assertEquals(100, (float) $bs->discount_per_session);
        $expectedTotal = (1000 - 100) * 2; // 1800
        $this->assertEquals($expectedTotal, (float) $bs->total_price);

        // Invoice total should reflect discount
        $invoice = $result['invoice'];
        $this->assertEquals($expectedTotal, (float) $invoice->total);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 3: Dermatology Consultation (No Service)
    // ═══════════════════════════════════════════════════

    public function test_full_dermatology_consultation_flow(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
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
                            'start_time' => '09:00',
                            'end_time' => '09:30',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];
        $this->assertEquals('dermatology_consultation', $booking->booking_type);
        $this->assertEquals('confirmed', $booking->status);

        // Booking service has null service_id
        $bs = $booking->bookingServices->first();
        $this->assertNull($bs->service_id);
        $this->assertEquals(200, (float) $bs->unit_price);
        $this->assertEquals(1, $bs->sessions_count);

        // Invoice item says "Dermatology Consultation"
        $invoice = $result['invoice'];
        $item = $invoice->items->first();
        $this->assertEquals('Dermatology Consultation', $item->description_en);
        $this->assertEquals(200, (float) $invoice->total);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 4: Cosmetic Consultation
    // ═══════════════════════════════════════════════════

    public function test_full_cosmetic_consultation_flow(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
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
                            'start_time' => '11:00',
                            'end_time' => '11:30',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];
        $this->assertEquals('cosmetic_consultation', $booking->booking_type);

        $bs = $booking->bookingServices->first();
        $this->assertNull($bs->service_id);
        $this->assertEquals(300, (float) $bs->unit_price);

        // Invoice item says "Cosmetic Consultation"
        $item = $result['invoice']->items->first();
        $this->assertEquals('Cosmetic Consultation', $item->description_en);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 5: Payment → Visit Auto-Creation
    // ═══════════════════════════════════════════════════

    public function test_payment_creates_visits_for_today_appointments(): void
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

        // Process payment
        $paymentResult = $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
        ], $this->admin->id);

        // Visit should be created for today's appointment
        $this->assertCount(1, $paymentResult['visits_created']);

        $visit = $paymentResult['visits_created'][0];
        $this->assertEquals($this->patient->id, $visit->patient_id);
        $this->assertEquals($this->doctor->id, $visit->doctor_id);
        $this->assertEquals('session', $visit->visit_type);
        $this->assertEquals('waiting', $visit->status);

        // Invoice should be paid
        $this->assertEquals('paid', $paymentResult['invoice']->status);

        // Booking should be in_progress
        $booking->refresh();
        $this->assertEquals('in_progress', $booking->status);
    }

    public function test_consultation_payment_creates_consultation_visit(): void
    {
        $today = now()->format('Y-m-d');

        $result = $this->workflowService->createFromSecretary([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
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
                            'date' => $today,
                            'start_time' => '10:00',
                            'end_time' => '10:30',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];

        $paymentResult = $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 200,
        ], $this->admin->id);

        $visit = $paymentResult['visits_created'][0];
        $this->assertEquals('consultation', $visit->visit_type);
        $this->assertEquals('dermatology', $visit->consultation_type);
        $this->assertNull($visit->service_id);
    }

    public function test_partial_payment_updates_invoice_correctly(): void
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
                    'unit_price' => 1000,
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

        // Pay half
        $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
        ], $this->admin->id);

        $invoice = Invoice::find($booking->invoice_id);
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(500, (float) $invoice->paid_amount);
        $this->assertEquals(500, (float) $invoice->balance);

        // Pay remaining
        $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
        ], $this->admin->id);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(1000, (float) $invoice->paid_amount);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 6: Visit Completion → Booking Completion
    // ═══════════════════════════════════════════════════

    public function test_visit_completion_updates_booking_service(): void
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

        // Process payment (creates visit)
        $paymentResult = $this->workflowService->processPayment($booking, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
        ], $this->admin->id);

        $visit = $paymentResult['visits_created'][0];

        // Simulate visit completion
        $this->workflowService->handleVisitCompleted($visit);

        // Appointment should be completed
        $appointment = BookingAppointment::where('visit_id', $visit->id)->first();
        $this->assertEquals('completed', $appointment->status);

        // Booking service sessions incremented
        $bs = BookingService::where('booking_id', $booking->id)->first();
        $this->assertEquals(1, $bs->completed_sessions);
        $this->assertEquals('completed', $bs->status);

        // Booking should be completed
        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 7: Visit Cancellation → Revert
    // ═══════════════════════════════════════════════════

    public function test_visit_cancellation_reverts_booking(): void
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

        // First complete the visit
        $this->workflowService->handleVisitCompleted($visit);

        // Then cancel it
        $visit->status = 'cancelled';
        $visit->save();
        $this->workflowService->handleVisitCancelled($visit);

        // Appointment should be reverted to scheduled
        $appointment = BookingAppointment::where('booking_id', $booking->id)->first();
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertNull($appointment->visit_id);

        // Booking service sessions decremented
        $bs = BookingService::where('booking_id', $booking->id)->first();
        $this->assertEquals(0, $bs->completed_sessions);
    }

    // ═══════════════════════════════════════════════════
    // FLOW 8: Multiple Services in One Booking
    // ═══════════════════════════════════════════════════

    public function test_multiple_services_booking(): void
    {
        $service2 = Service::create([
            'category_id' => $this->service->category_id,
            'name_ar' => 'خدمة ثانية',
            'name_en' => 'Second Service',
            'slug' => 'second-service',
            'status' => 'active',
            'bookable' => true,
            'show_on_website' => true,
            'price' => 800,
        ]);

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
                [
                    'service_id' => $service2->id,
                    'doctor_id' => $this->doctor->id,
                    'sessions_count' => 1,
                    'unit_price' => 800,
                    'discount_per_session' => 0,
                    'appointments' => [
                        [
                            'doctor_id' => $this->doctor->id,
                            'date' => $tomorrow,
                            'start_time' => '11:00',
                            'end_time' => '11:45',
                        ],
                    ],
                ],
            ],
        ], $this->admin->id);

        $booking = $result['booking'];

        // 2 booking services
        $this->assertCount(2, $booking->bookingServices);

        // 3 total appointments
        $this->assertCount(3, $booking->appointments);

        // Invoice total = (1000 * 2) + (800 * 1) = 2800
        $invoice = $result['invoice'];
        $this->assertEquals(2800, (float) $invoice->total);

        // 2 invoice items
        $this->assertCount(2, $invoice->items);
    }

    // ═══════════════════════════════════════════════════
    // MODEL TESTS
    // ═══════════════════════════════════════════════════

    public function test_booking_number_generation(): void
    {
        $number1 = Booking::generateBookingNumber();
        $this->assertStringStartsWith('BK-', $number1);

        Booking::create([
            'booking_number' => $number1,
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Test',
            'phone' => '012',
        ]);

        $number2 = Booking::generateBookingNumber();
        $this->assertNotEquals($number1, $number2);
    }

    public function test_invoice_number_generation(): void
    {
        $number1 = Invoice::generateInvoiceNumber();
        $this->assertStringStartsWith('INV-', $number1);
    }

    public function test_patient_file_number_generation(): void
    {
        $number = Patient::generateFileNumber();
        $this->assertStringStartsWith('P-', $number);
        $this->assertEquals(7, strlen($number)); // P-00001
    }

    public function test_booking_service_remaining_sessions(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-TEST-0001',
            'source' => 'secretary',
            'status' => 'confirmed',
            'full_name' => 'Test',
            'phone' => '012',
            'patient_id' => $this->patient->id,
        ]);

        $bs = BookingService::create([
            'booking_id' => $booking->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 5,
            'unit_price' => 100,
            'total_price' => 500,
            'completed_sessions' => 2,
            'status' => 'in_progress',
        ]);

        $this->assertEquals(3, $bs->remaining_sessions);
    }

    public function test_invoice_balance_accessor(): void
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-TEST-0001',
            'invoice_date' => now()->toDateString(),
            'patient_id' => $this->patient->id,
            'subtotal' => 1000,
            'total' => 1000,
            'created_by' => $this->admin->id,
        ]);
        $invoice->paid_amount = 400;
        $invoice->status = 'partial';
        $invoice->save();

        $this->assertEquals(600, (float) $invoice->balance);
    }

    public function test_invoice_recalculate_status(): void
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-TEST-0002',
            'invoice_date' => now()->toDateString(),
            'patient_id' => $this->patient->id,
            'subtotal' => 500,
            'total' => 500,
            'created_by' => $this->admin->id,
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        // Add partial payment
        Payment::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $this->patient->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 200,
            'payment_date' => now()->toDateString(),
            'received_by' => $this->admin->id,
        ]);

        $invoice->recalculateStatus();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(200, (float) $invoice->paid_amount);

        // Add remaining
        Payment::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $this->patient->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 300,
            'payment_date' => now()->toDateString(),
            'received_by' => $this->admin->id,
        ]);

        $invoice->recalculateStatus();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(500, (float) $invoice->paid_amount);
    }

    // ═══════════════════════════════════════════════════
    // ROLE & PERMISSION TESTS
    // ═══════════════════════════════════════════════════

    public function test_role_wildcard_permission(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'super'],
            [
                'display_name_en' => 'Super',
                'display_name_ar' => 'سوبر',
                'permissions' => ['*'],
            ]
        );

        $this->assertTrue($role->hasPermission('bookings.create'));
        $this->assertTrue($role->hasPermission('anything'));
    }

    public function test_role_specific_permissions(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'limited'],
            [
                'display_name_en' => 'Limited',
                'display_name_ar' => 'محدود',
                'permissions' => ['bookings.view', 'patients.view'],
            ]
        );

        $this->assertTrue($role->hasPermission('bookings.view'));
        $this->assertTrue($role->hasPermission('patients.view'));
        $this->assertFalse($role->hasPermission('bookings.create'));
        $this->assertFalse($role->hasPermission('settings.manage'));
    }

    public function test_user_has_role(): void
    {
        // admin role already created in setUp
        $this->assertTrue($this->admin->hasRole('admin'));
        $this->assertFalse($this->admin->hasRole('secretary'));
    }
}

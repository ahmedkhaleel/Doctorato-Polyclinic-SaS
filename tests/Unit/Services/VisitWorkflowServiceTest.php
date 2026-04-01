<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Patient $patient;
    protected Doctor $doctor;
    protected Service $service;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
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
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01000000000']);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->save();

        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            [
                'display_name_en' => 'Doctor',
                'display_name_ar' => 'طبيب',
                'permissions' => ['doctor.*'],
                'is_system' => true,
            ]
        );

        $doctorUser = User::create([
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com',
            'password' => bcrypt('password'),
            'role_id' => $doctorRole->id,
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور تجربة',
            'name_en' => 'Dr. Test',
            'status' => 'active',
            'user_id' => $doctorUser->id,
            'dermatology_fee' => 200,
            'cosmetic_fee' => 300,
            'default_commission_percentage' => 30,
        ]);

        $this->category = ServiceCategory::create([
            'name_ar' => 'تصنيف',
            'name_en' => 'Test Category',
            'slug' => 'test-cat',
        ]);

        $this->service = Service::create([
            'category_id' => $this->category->id,
            'name_ar' => 'خدمة تجريبية',
            'name_en' => 'Test Service',
            'slug' => 'test-service',
            'status' => 'active',
            'bookable' => true,
            'show_on_website' => true,
            'price' => 500,
            'default_sessions' => 1,
            'session_duration_minutes' => 30,
        ]);

        // SMS disabled by default to avoid side-effects in tests
        Setting::set('sms_on_visit_completed', '0');

        $this->actingAs($this->admin);
    }

    // ─── Helper ─────────────────────────────────────────

    /**
     * Create a visit linked to a booking (satisfies the Visit boot constraint).
     */
    protected function createVisitWithBooking(array $overrides = []): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id,
            'booking_number' => Booking::generateBookingNumber(),
            'source' => 'clinic',
            'booking_type' => 'dermatology_consultation',
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed',
        ]);

        $defaults = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'waiting',
            'visit_date' => now()->toDateString(),
            'scheduled_time' => '10:00',
        ];

        return Visit::create(array_merge($defaults, $overrides));
    }

    /**
     * Create a visit without booking_id (bypasses boot constraint for auto-invoice tests).
     */
    protected function createVisitWithoutBooking(array $overrides = []): Visit
    {
        $defaults = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'waiting',
            'visit_date' => now()->toDateString(),
            'scheduled_time' => '10:00',
        ];

        $attributes = array_merge($defaults, $overrides);

        // Use withoutEvents to bypass the boot constraint requiring booking_id
        $visit = Visit::withoutEvents(function () use ($attributes) {
            return Visit::create($attributes);
        });

        return $visit->fresh();
    }

    // ─── start() Tests ──────────────────────────────────

    public function test_start_changes_status_to_in_progress(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'waiting']);

        $service = app(VisitWorkflowService::class);
        $result = $service->start($visit);

        $this->assertEquals('in_progress', $result->status);
        $this->assertEquals('in_progress', $visit->fresh()->status);
    }

    public function test_start_sets_started_at_timestamp(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'waiting']);

        $this->assertNull($visit->started_at);

        $service = app(VisitWorkflowService::class);
        $service->start($visit);

        $visit->refresh();
        $this->assertNotNull($visit->started_at);
        $this->assertEqualsWithDelta(now()->timestamp, $visit->started_at->timestamp, 5);
    }

    // ─── complete() Tests ───────────────────────────────

    public function test_complete_changes_status_to_completed(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'in_progress']);

        $service = app(VisitWorkflowService::class);
        $service->complete($visit);

        $this->assertEquals('completed', $visit->fresh()->status);
    }

    public function test_complete_sets_completed_at_timestamp(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'in_progress']);

        $this->assertNull($visit->completed_at);

        $service = app(VisitWorkflowService::class);
        $service->complete($visit);

        $visit->refresh();
        $this->assertNotNull($visit->completed_at);
        $this->assertEqualsWithDelta(now()->timestamp, $visit->completed_at->timestamp, 5);
    }

    public function test_complete_auto_generates_invoice_for_non_booking_consultation(): void
    {
        $visit = $this->createVisitWithoutBooking([
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'in_progress',
        ]);

        // Confirm no invoice exists before completion
        $this->assertNull(Invoice::where('visit_id', $visit->id)->first());

        $service = app(VisitWorkflowService::class);
        $results = $service->complete($visit);

        $this->assertNotNull($results['invoice']);
        $this->assertInstanceOf(Invoice::class, $results['invoice']);
        $this->assertEquals($visit->id, $results['invoice']->visit_id);
        $this->assertEquals($this->patient->id, $results['invoice']->patient_id);
        $this->assertEquals('unpaid', $results['invoice']->status);
        $this->assertEquals(0, (float) $results['invoice']->paid_amount);
        $this->assertEquals($this->admin->id, $results['invoice']->created_by);
    }

    public function test_complete_auto_invoice_has_correct_invoice_item(): void
    {
        $visit = $this->createVisitWithoutBooking([
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'in_progress',
        ]);

        $service = app(VisitWorkflowService::class);
        $results = $service->complete($visit);

        $invoice = $results['invoice'];
        $items = InvoiceItem::where('invoice_id', $invoice->id)->get();

        $this->assertCount(1, $items);

        $item = $items->first();
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals('Dermatology Consultation', $item->description_en);
        $this->assertEquals('كشف جلدية', $item->description_ar);
        // Price should match the doctor's dermatology_fee (200)
        $this->assertEquals(200, (float) $item->unit_price);
        $this->assertEquals(200, (float) $item->total);
    }

    public function test_complete_does_not_generate_invoice_for_booking_linked_visit(): void
    {
        $visit = $this->createVisitWithBooking([
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'in_progress',
        ]);

        $service = app(VisitWorkflowService::class);
        $results = $service->complete($visit);

        // Invoice should be null because the visit has a booking_id
        $this->assertNull($results['invoice']);
    }

    public function test_complete_calculates_commission_for_non_booking_visit(): void
    {
        $visit = $this->createVisitWithoutBooking([
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'in_progress',
        ]);

        $service = app(VisitWorkflowService::class);
        $results = $service->complete($visit);

        $visit->refresh();

        // Doctor has default_commission_percentage=30 and dermatology_fee=200
        // Commission = 200 * 30% = 60 (using default_commission_percentage since no dermatology_commission set)
        $this->assertNotNull($visit->commission_rate);
        $this->assertNotNull($visit->commission_amount);
        $this->assertEquals(30, (float) $visit->commission_rate);
        $this->assertEquals(60, (float) $visit->commission_amount);
    }

    // ─── cancel() Tests ─────────────────────────────────

    public function test_cancel_changes_status_to_cancelled(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'waiting']);

        $service = app(VisitWorkflowService::class);
        $service->cancel($visit);

        $this->assertEquals('cancelled', $visit->fresh()->status);
    }

    public function test_cancel_works_for_waiting_visit(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'waiting']);

        $service = app(VisitWorkflowService::class);

        // Should not throw any exception
        $service->cancel($visit);

        $visit->refresh();
        $this->assertEquals('cancelled', $visit->status);
    }

    public function test_cancel_works_for_in_progress_visit(): void
    {
        $visit = $this->createVisitWithBooking([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $service = app(VisitWorkflowService::class);

        // Should not throw any exception
        $service->cancel($visit);

        $visit->refresh();
        $this->assertEquals('cancelled', $visit->status);
    }

    public function test_complete_returns_array_with_expected_keys(): void
    {
        $visit = $this->createVisitWithBooking([
            'visit_type' => 'consultation',
            'consultation_type' => 'dermatology',
            'status' => 'in_progress',
        ]);

        $service = app(VisitWorkflowService::class);
        $results = $service->complete($visit);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('invoice', $results);
        $this->assertArrayHasKey('inventory', $results);
    }
}

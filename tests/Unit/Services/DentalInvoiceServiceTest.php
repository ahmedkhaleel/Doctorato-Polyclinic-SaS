<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\DentalLabOrder;
use App\Models\DentalTreatment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Services\DentalInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentalInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DentalInvoiceService $service;
    protected User $admin;
    protected Patient $patient;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Test Patient',
            'phone' => '0501234567',
            'gender' => 'male',
        ]);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_en' => 'Dr. Test',
            'name_ar' => 'د. اختبار',
            'status' => 'active',
            'module' => 'dental',
        ]);

        $this->actingAs($this->admin);

        $this->service = app(DentalInvoiceService::class);
    }

    /**
     * Helper to create a booking and visit for use in treatments.
     */
    protected function createVisit(): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id,
            'booking_number' => 'BK-' . uniqid(),
            'source' => 'clinic',
            'module' => 'dental',
            'booking_type' => 'dental_consultation',
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'doctor_id' => $this->doctor->id,
            'preferred_date' => now()->toDateString(),
            'status' => 'confirmed',
        ]);

        return Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation',
            'module' => 'dental',
            'status' => 'in_progress',
            'visit_date' => now()->toDateString(),
        ]);
    }

    /**
     * Helper to create a completed dental treatment without triggering the updated observer.
     */
    protected function createCompletedTreatment(array $overrides = []): DentalTreatment
    {
        $visit = $overrides['visit_id'] ?? null ? null : $this->createVisit();

        return DentalTreatment::create(array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'visit_id' => $visit?->id,
            'treatment_plan_id' => null,
            'tooth_number' => 14,
            'treatment_type' => 'filling',
            'cost' => 200.00,
            'lab_cost' => 0,
            'status' => 'completed',
            'completed_at' => now(),
        ], $overrides));
    }

    // ─── generateForCompletedTreatment Tests ───────────────────

    public function test_returns_null_when_treatment_status_is_not_completed(): void
    {
        $treatment = DentalTreatment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'visit_id' => $this->createVisit()->id,
            'treatment_plan_id' => null,
            'tooth_number' => 14,
            'treatment_type' => 'filling',
            'cost' => 200.00,
            'lab_cost' => 0,
            'status' => 'planned',
        ]);

        $result = $this->service->generateForCompletedTreatment($treatment);

        $this->assertNull($result);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_returns_null_when_treatment_cost_is_zero(): void
    {
        $treatment = $this->createCompletedTreatment(['cost' => 0]);

        $result = $this->service->generateForCompletedTreatment($treatment);

        $this->assertNull($result);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_returns_null_when_treatment_cost_is_negative(): void
    {
        $treatment = $this->createCompletedTreatment(['cost' => -50]);

        $result = $this->service->generateForCompletedTreatment($treatment);

        $this->assertNull($result);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_creates_invoice_and_item_for_completed_treatment(): void
    {
        $treatment = $this->createCompletedTreatment([
            'cost' => 300.00,
            'treatment_type' => 'extraction',
            'tooth_number' => 36,
        ]);

        $invoice = $this->service->generateForCompletedTreatment($treatment);

        $this->assertNotNull($invoice);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($this->patient->id, $invoice->patient_id);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals(300.00, (float) $invoice->total);

        $this->assertDatabaseCount('invoice_items', 1);
        $item = $invoice->items->first();
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals(300.00, (float) $item->unit_price);
        $this->assertEquals(300.00, (float) $item->total);
        $this->assertStringContains('Extraction', $item->description_en);
        $this->assertStringContains('#36', $item->description_en);
    }

    public function test_creates_correct_arabic_description_for_filling(): void
    {
        $treatment = $this->createCompletedTreatment([
            'treatment_type' => 'filling',
            'tooth_number' => 21,
        ]);

        $invoice = $this->service->generateForCompletedTreatment($treatment);

        $item = $invoice->items->first();
        $this->assertStringContains('حشو', $item->description_ar);
        $this->assertStringContains('#21', $item->description_ar);
    }

    public function test_creates_correct_arabic_description_for_root_canal(): void
    {
        $treatment = $this->createCompletedTreatment([
            'treatment_type' => 'root_canal',
            'tooth_number' => 46,
        ]);

        $invoice = $this->service->generateForCompletedTreatment($treatment);

        $item = $invoice->items->first();
        $this->assertStringContains('علاج عصب', $item->description_ar);
        $this->assertEquals('Root canal (#46)', $item->description_en);
    }

    public function test_links_treatment_to_invoice(): void
    {
        $treatment = $this->createCompletedTreatment();

        $invoice = $this->service->generateForCompletedTreatment($treatment);

        $treatment->refresh();
        $this->assertEquals($invoice->id, $treatment->invoice_id);
    }

    public function test_adds_to_existing_visit_invoice_instead_of_creating_new(): void
    {
        $visit = $this->createVisit();

        // Create first treatment and generate its invoice
        $treatment1 = $this->createCompletedTreatment([
            'visit_id' => $visit->id,
            'treatment_type' => 'filling',
            'cost' => 200.00,
        ]);
        $invoice1 = $this->service->generateForCompletedTreatment($treatment1);

        // Create second treatment for the same visit
        $treatment2 = $this->createCompletedTreatment([
            'visit_id' => $visit->id,
            'treatment_type' => 'extraction',
            'cost' => 150.00,
        ]);
        $invoice2 = $this->service->generateForCompletedTreatment($treatment2);

        // Should reuse the same invoice
        $this->assertEquals($invoice1->id, $invoice2->id);
        $this->assertDatabaseCount('invoices', 1);

        // Invoice should now have 2 items with combined total
        $invoice2->refresh();
        $this->assertEquals(2, $invoice2->items()->count());
        $this->assertEquals(350.00, (float) $invoice2->total);
    }

    public function test_skips_if_treatment_already_has_invoice_id(): void
    {
        $treatment = $this->createCompletedTreatment(['cost' => 200.00]);

        // Generate invoice the first time
        $invoice = $this->service->generateForCompletedTreatment($treatment);
        $this->assertNotNull($invoice);

        // Calling again should return the existing invoice without creating new items
        $treatment->refresh();
        $result = $this->service->generateForCompletedTreatment($treatment);

        $this->assertNotNull($result);
        $this->assertEquals($invoice->id, $result->id);
        $this->assertDatabaseCount('invoice_items', 1);
    }

    public function test_creates_invoice_without_tooth_number(): void
    {
        $treatment = $this->createCompletedTreatment([
            'treatment_type' => 'cleaning',
            'tooth_number' => null,
            'cost' => 100.00,
        ]);

        $invoice = $this->service->generateForCompletedTreatment($treatment);

        $item = $invoice->items->first();
        $this->assertEquals('Cleaning', $item->description_en);
        $this->assertEquals('تنظيف', $item->description_ar);
    }

    // ─── addLabOrderToInvoice Tests ────────────────────────────

    public function test_add_lab_order_returns_null_when_patient_charge_is_zero(): void
    {
        $treatment = $this->createCompletedTreatment();

        $labOrder = DentalLabOrder::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'dental_treatment_id' => $treatment->id,
            'lab_name' => 'Test Lab',
            'order_number' => 'LAB-001',
            'item_type' => 'crown',
            'tooth_number' => 14,
            'material' => 'zirconia',
            'cost' => 500.00,
            'patient_charge' => 0,
            'status' => 'delivered',
            'order_date' => now()->toDateString(),
        ]);

        $result = $this->service->addLabOrderToInvoice($labOrder);

        $this->assertNull($result);
    }

    public function test_add_lab_order_creates_invoice_item(): void
    {
        $treatment = $this->createCompletedTreatment();

        // First generate the treatment invoice so treatment has invoice_id
        $this->service->generateForCompletedTreatment($treatment);
        $treatment->refresh();

        $labOrder = DentalLabOrder::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'dental_treatment_id' => $treatment->id,
            'lab_name' => 'Test Lab',
            'order_number' => 'LAB-002',
            'item_type' => 'crown',
            'tooth_number' => 14,
            'material' => 'zirconia',
            'cost' => 500.00,
            'patient_charge' => 800.00,
            'status' => 'delivered',
            'order_date' => now()->toDateString(),
        ]);

        $invoiceItem = $this->service->addLabOrderToInvoice($labOrder);

        $this->assertNotNull($invoiceItem);
        $this->assertInstanceOf(InvoiceItem::class, $invoiceItem);
        $this->assertEquals(800.00, (float) $invoiceItem->unit_price);
        $this->assertEquals(800.00, (float) $invoiceItem->total);
        $this->assertStringContains('Lab:', $invoiceItem->description_en);
        $this->assertStringContains('Crown', $invoiceItem->description_en);
        $this->assertStringContains('Zirconia', $invoiceItem->description_en);
        $this->assertStringContains('#14', $invoiceItem->description_en);
        $this->assertStringContains('معمل:', $invoiceItem->description_ar);
        $this->assertStringContains('تاج', $invoiceItem->description_ar);
    }

    public function test_add_lab_order_links_lab_order_to_invoice_item(): void
    {
        $treatment = $this->createCompletedTreatment();
        $this->service->generateForCompletedTreatment($treatment);
        $treatment->refresh();

        $labOrder = DentalLabOrder::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'dental_treatment_id' => $treatment->id,
            'lab_name' => 'Test Lab',
            'order_number' => 'LAB-003',
            'item_type' => 'veneer',
            'tooth_number' => 11,
            'material' => 'emax',
            'cost' => 400.00,
            'patient_charge' => 700.00,
            'status' => 'delivered',
            'order_date' => now()->toDateString(),
        ]);

        $invoiceItem = $this->service->addLabOrderToInvoice($labOrder);

        $labOrder->refresh();
        $this->assertEquals($invoiceItem->id, $labOrder->invoice_item_id);
    }

    public function test_add_lab_order_skips_if_already_linked_to_invoice_item(): void
    {
        $treatment = $this->createCompletedTreatment();
        $this->service->generateForCompletedTreatment($treatment);
        $treatment->refresh();

        $labOrder = DentalLabOrder::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'dental_treatment_id' => $treatment->id,
            'lab_name' => 'Test Lab',
            'order_number' => 'LAB-004',
            'item_type' => 'crown',
            'tooth_number' => 14,
            'material' => 'zirconia',
            'cost' => 500.00,
            'patient_charge' => 800.00,
            'status' => 'delivered',
            'order_date' => now()->toDateString(),
        ]);

        // First call
        $invoiceItem1 = $this->service->addLabOrderToInvoice($labOrder);
        $labOrder->refresh();

        // Second call should return existing item without creating a new one
        $invoiceItem2 = $this->service->addLabOrderToInvoice($labOrder);

        $this->assertEquals($invoiceItem1->id, $invoiceItem2->id);

        // Only the treatment item + 1 lab item should exist (2 total on the invoice)
        $invoice = Invoice::find($treatment->invoice_id);
        $this->assertEquals(2, $invoice->items()->count());
    }

    /**
     * Custom assertion: check that a string contains a substring.
     */
    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringContainsString($needle, $haystack, $message);
    }
}

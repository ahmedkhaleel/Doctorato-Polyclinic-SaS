<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Patient $patient;

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

        $this->paymentMethod = PaymentMethod::create([
            'name_ar' => 'كاش',
            'name_en' => 'Cash',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    // ─── Index ─────────────────────────────────────────

    public function test_admin_can_view_invoices_index(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/invoices');
        $response->assertStatus(200);
    }

    // ─── Show ──────────────────────────────────────────

    public function test_admin_can_view_invoice_details(): void
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-TEST-0001',
            'invoice_date' => now()->toDateString(),
            'patient_id' => $this->patient->id,
            'subtotal' => 500,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 500,
            'created_by' => $this->admin->id,
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        $this->actingAs($this->admin);

        $response = $this->get("/admin/invoices/{$invoice->id}");
        $response->assertStatus(200);
    }

    // ─── Store ─────────────────────────────────────────

    public function test_admin_can_create_invoice(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/invoices', [
            'patient_id' => $this->patient->id,
            'items' => [
                [
                    'description_ar' => 'خدمة تجريبية',
                    'description_en' => 'Test Service',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Invoice created successfully.');

        $this->assertDatabaseHas('invoices', [
            'patient_id' => $this->patient->id,
            'status' => 'unpaid',
        ]);

        $invoice = Invoice::latest()->first();
        $this->assertEquals(500.00, (float) $invoice->total);
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
    }

    // ─── Validation ────────────────────────────────────

    public function test_invoice_requires_patient_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/invoices', [
            'items' => [
                [
                    'description_ar' => 'خدمة',
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('patient_id');
    }

    public function test_invoice_requires_at_least_one_item(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/invoices', [
            'patient_id' => $this->patient->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_invoice_total_cannot_be_negative(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/invoices', [
            'patient_id' => $this->patient->id,
            'discount_amount' => 9999,
            'items' => [
                [
                    'description_ar' => 'خدمة',
                    'description_en' => 'Service',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount' => 0,
                ],
            ],
        ]);

        // The controller throws RuntimeException caught as session error
        $response->assertSessionHas('error', 'Discount amount cannot exceed the subtotal.');
    }
}

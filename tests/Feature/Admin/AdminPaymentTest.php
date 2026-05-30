<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Patient $patient;

    protected Invoice $invoice;

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

        $this->invoice = new Invoice([
            'invoice_number' => 'INV-TEST-0001',
            'invoice_date' => now()->toDateString(),
            'patient_id' => $this->patient->id,
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 1000,
            'created_by' => $this->admin->id,
        ]);
        $this->invoice->paid_amount = 0;
        $this->invoice->status = 'unpaid';
        $this->invoice->save();
    }

    // ─── Index ─────────────────────────────────────────

    public function test_admin_can_view_payments_index(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/payments');
        $response->assertStatus(200);
    }

    // ─── Store ─────────────────────────────────────────

    public function test_admin_can_create_payment(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Payment recorded successfully.');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 500,
        ]);

        // Invoice should be partial
        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
        $this->assertEquals(500.00, (float) $this->invoice->paid_amount);
    }

    // ─── Validation ────────────────────────────────────

    public function test_payment_requires_invoice_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/payments', [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 100,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('invoice_id');
    }

    public function test_payment_requires_amount(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_payment_amount_must_be_positive(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 0,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 2000, // Invoice total is 1000
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
    }
}

<?php

namespace Tests\Feature\Secretary;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaryUser;
    private Patient $patient;
    private Invoice $invoice;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتيرة', 'permissions' => [], 'is_system' => true]
        );

        $this->secretaryUser = User::create([
            'name' => 'Secretary', 'email' => 'sec-payment@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient(['full_name' => 'Payment Patient', 'phone' => '01777000111']);
        $this->patient->file_number = 'P-PAY-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->invoice = Invoice::create([
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-TEST-001',
            'total' => 500.00,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'invoice_date' => now()->toDateString(),
        ]);

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['name_en' => 'Cash'],
            ['name_ar' => 'نقدي', 'is_active' => true]
        );
    }

    public function test_secretary_can_view_payments_index(): void
    {
        $this->actingAs($this->secretaryUser)
            ->get('/secretary/payments')
            ->assertOk();
    }

    public function test_secretary_can_record_payment(): void
    {
        $this->actingAs($this->secretaryUser)
            ->post('/secretary/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_method_id' => $this->paymentMethod->id,
                'amount' => 200.00,
                'payment_date' => now()->toDateString(),
                'notes' => 'Partial payment',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 200.00,
        ]);

        $this->invoice->refresh();
        $this->assertEquals(200.00, (float) $this->invoice->paid_amount);
    }

    public function test_payment_prevents_overpayment(): void
    {
        $this->actingAs($this->secretaryUser)
            ->post('/secretary/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_method_id' => $this->paymentMethod->id,
                'amount' => 600.00, // More than total 500
                'payment_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_payment_requires_valid_amount(): void
    {
        $this->actingAs($this->secretaryUser)
            ->post('/secretary/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_method_id' => $this->paymentMethod->id,
                'amount' => 0, // Below minimum
                'payment_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_payment_requires_valid_invoice(): void
    {
        $this->actingAs($this->secretaryUser)
            ->post('/secretary/payments', [
                'invoice_id' => 99999, // Non-existent
                'payment_method_id' => $this->paymentMethod->id,
                'amount' => 100.00,
                'payment_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('invoice_id');
    }

    public function test_payment_updates_invoice_status_to_partial(): void
    {
        $this->actingAs($this->secretaryUser)
            ->post('/secretary/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_method_id' => $this->paymentMethod->id,
                'amount' => 250.00,
                'payment_date' => now()->toDateString(),
            ]);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
    }

    public function test_payment_updates_invoice_status_to_paid(): void
    {
        $this->actingAs($this->secretaryUser)
            ->post('/secretary/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_method_id' => $this->paymentMethod->id,
                'amount' => 500.00,
                'payment_date' => now()->toDateString(),
            ]);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
    }

    public function test_secretary_can_search_payments(): void
    {
        $this->actingAs($this->secretaryUser)
            ->get('/secretary/payments?search=INV-TEST')
            ->assertOk();
    }

    public function test_secretary_can_filter_payments_by_date(): void
    {
        $this->actingAs($this->secretaryUser)
            ->get('/secretary/payments?date_from=' . now()->toDateString() . '&date_to=' . now()->toDateString())
            ->assertOk();
    }
}

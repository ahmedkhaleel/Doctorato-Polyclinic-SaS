<?php

namespace Tests\Feature\Security;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Invoice $invoice;
    private PaymentMethod $paymentMethod;

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

        $this->patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01000000001']);
        $this->patient->file_number = 'P-TEST-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->invoice = new Invoice([
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => now()->toDateString(),
            'patient_id' => $this->patient->id,
            'subtotal' => 1000,
            'total' => 1000,
            'created_by' => $this->admin->id,
        ]);
        $this->invoice->paid_amount = 0;
        $this->invoice->status = 'unpaid';
        $this->invoice->save();

        $this->paymentMethod = PaymentMethod::create([
            'name_en' => 'Cash',
            'name_ar' => 'نقدي',
            'is_active' => true,
        ]);
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 1500, // exceeds the 1000 total
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_valid_payment_is_recorded(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('payments', 1);

        $this->invoice->refresh();
        $this->assertEquals(500, (float) $this->invoice->paid_amount);
    }

    public function test_partial_payment_updates_invoice_status(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
        ]);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
    }

    public function test_full_payment_marks_invoice_paid(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
        ]);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
    }

    public function test_second_payment_cannot_exceed_remaining_balance(): void
    {
        $this->actingAs($this->admin);

        // First payment
        $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 800,
            'payment_date' => now()->toDateString(),
        ]);

        // Second payment exceeding remaining 200
        $response = $this->post('/admin/payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 300,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 1);
    }
}

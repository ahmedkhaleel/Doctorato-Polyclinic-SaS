<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_amount_is_not_mass_assignable(): void
    {
        $this->assertNotContains('paid_amount', (new Invoice)->getFillable());
    }

    public function test_status_is_not_mass_assignable(): void
    {
        $this->assertNotContains('status', (new Invoice)->getFillable());
    }

    public function test_mass_assignment_cannot_set_financial_state(): void
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-TEST',
            'patient_id' => 1,
            'total' => 1000,
            'paid_amount' => 1000,
            'status' => 'paid',
        ]);

        // These should NOT be set via mass-assignment
        $this->assertNull($invoice->paid_amount);
        $this->assertNull($invoice->status);
        // These should be set
        $this->assertEquals('INV-TEST', $invoice->invoice_number);
        $this->assertEquals(1000, $invoice->total);
    }

    public function test_financial_fields_can_be_set_directly(): void
    {
        $invoice = new Invoice(['invoice_number' => 'INV-001', 'total' => 500]);
        $invoice->status = 'unpaid';
        $invoice->paid_amount = 0;

        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals(0, $invoice->paid_amount);
    }
}

<?php

namespace Tests\Feature\Secretary;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتارية', 'permissions' => [
                'patients.view', 'invoices.view', 'invoices.create', 'payments.view', 'payments.create',
            ], 'is_system' => true]
        );

        $this->secretary = User::create([
            'name' => 'Secretary', 'email' => 'sec-inv@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient(['full_name' => 'Invoice Patient', 'phone' => '01777000002']);
        $this->patient->file_number = 'P-SI-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    public function test_can_view_invoices_index(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/invoices')->assertOk();
    }

    public function test_can_view_create_page(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/invoices/create')->assertOk();
    }

    public function test_can_create_invoice(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/invoices', [
            'patient_id' => $this->patient->id,
            'items' => [
                [
                    'description_ar' => 'كشف جلدية',
                    'description_en' => 'Derma Consultation',
                    'quantity' => 1,
                    'unit_price' => 300,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', ['patient_id' => $this->patient->id]);
    }

    public function test_invoice_requires_patient(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/invoices', [
            'items' => [
                ['description_ar' => 'Test', 'quantity' => 1, 'unit_price' => 100],
            ],
        ])->assertSessionHasErrors('patient_id');
    }

    public function test_invoice_requires_items(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/invoices', [
            'patient_id' => $this->patient->id,
            'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_can_view_invoice_show(): void
    {
        $invoice = Invoice::create([
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-SEC-001',
            'total' => 300,
            'invoice_date' => now()->toDateString(),
            'created_by' => $this->secretary->id,
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        $this->actingAs($this->secretary)
            ->get("/secretary/invoices/{$invoice->id}")
            ->assertOk();
    }

    public function test_unauthenticated_cannot_access(): void
    {
        $response = $this->get('/secretary/invoices');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}

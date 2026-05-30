<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Patient $patient;

    protected Invoice $invoice;

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
        $this->invoice->paid_amount = 1000;
        $this->invoice->status = 'paid';
        $this->invoice->save();
    }

    // ─── Index ─────────────────────────────────────────

    public function test_admin_can_view_credit_notes_index(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/credit-notes');
        $response->assertStatus(200);
    }

    // ─── Store ─────────────────────────────────────────

    public function test_admin_can_create_credit_note(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/credit-notes', [
            'invoice_id' => $this->invoice->id,
            'type' => 'partial_refund',
            'amount' => 200,
            'reason' => 'Patient requested partial refund',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('credit_notes', [
            'invoice_id' => $this->invoice->id,
            'amount' => 200,
            'type' => 'partial_refund',
            'status' => 'draft',
        ]);
    }

    public function test_credit_note_cannot_exceed_invoice_total(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/credit-notes', [
            'invoice_id' => $this->invoice->id,
            'type' => 'full_refund',
            'amount' => 5000, // Invoice total is 1000
            'reason' => 'Overshoot refund',
        ]);

        // Controller catches RuntimeException and returns with error
        $response->assertSessionHas('error', 'Refund amount exceeds invoice total.');
    }

    // ─── Validation ────────────────────────────────────

    public function test_credit_note_requires_invoice_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/credit-notes', [
            'type' => 'partial_refund',
            'amount' => 100,
            'reason' => 'Test reason',
        ]);

        $response->assertSessionHasErrors('invoice_id');
    }

    public function test_credit_note_requires_amount(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/credit-notes', [
            'invoice_id' => $this->invoice->id,
            'type' => 'partial_refund',
            'reason' => 'Test reason',
        ]);

        $response->assertSessionHasErrors('amount');
    }
}

<?php

namespace Tests\Unit\Policies;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    private InvoicePolicy $policy;
    private User $admin;
    private User $patientUser;
    private User $secretaryUser;
    private Patient $patient;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new InvoicePolicy();

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password',
            'role_id' => $adminRole->id, 'is_active' => true,
        ]);

        $secretaryRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتارية',
             'permissions' => ['invoices.view', 'invoices.create', 'invoices.update'], 'is_system' => true]
        );
        $this->secretaryUser = User::create([
            'name' => 'Secretary', 'email' => 'secretary@test.com', 'password' => 'password',
            'role_id' => $secretaryRole->id, 'is_active' => true,
        ]);

        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );
        $this->patientUser = User::create([
            'name' => 'Patient', 'email' => 'patient@test.com', 'password' => 'password',
            'role_id' => $patientRole->id, 'is_active' => true,
        ]);
        $this->patient = new Patient(['full_name' => 'Test Patient', 'phone' => '0500000000']);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->user_id = $this->patientUser->id;
        $this->patient->save();

        $this->invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'patient_id' => $this->patient->id,
            'subtotal' => 500, 'total' => 500,
            'created_by' => $this->admin->id,
        ]);
        $this->invoice->paid_amount = 0;
        $this->invoice->status = 'unpaid';
        $this->invoice->save();
    }

    public function test_admin_can_view_any_invoice(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->invoice));
    }

    public function test_patient_can_view_own_invoice(): void
    {
        $this->assertTrue($this->policy->view($this->patientUser, $this->invoice));
    }

    public function test_patient_cannot_view_others_invoice(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other', 'phone' => '0501111111']);
        $otherPatient->file_number = Patient::generateFileNumber();
        $otherPatient->is_active = true;
        $otherPatient->save();

        $otherInvoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'patient_id' => $otherPatient->id,
            'subtotal' => 200, 'total' => 200,
            'created_by' => $this->admin->id,
        ]);
        $otherInvoice->paid_amount = 0;
        $otherInvoice->status = 'unpaid';
        $otherInvoice->save();

        $this->assertFalse($this->policy->view($this->patientUser, $otherInvoice));
    }

    public function test_secretary_can_update_unpaid_invoice(): void
    {
        $this->assertTrue($this->policy->update($this->secretaryUser, $this->invoice));
    }

    public function test_secretary_cannot_update_paid_invoice(): void
    {
        $this->invoice->status = 'paid';
        $this->invoice->save();

        $this->assertFalse($this->policy->update($this->secretaryUser, $this->invoice));
    }

    public function test_admin_can_delete_invoice(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->invoice));
    }

    public function test_secretary_cannot_delete_invoice(): void
    {
        $this->assertFalse($this->policy->delete($this->secretaryUser, $this->invoice));
    }

    public function test_patient_cannot_create_invoice(): void
    {
        $this->assertFalse($this->policy->create($this->patientUser));
    }
}

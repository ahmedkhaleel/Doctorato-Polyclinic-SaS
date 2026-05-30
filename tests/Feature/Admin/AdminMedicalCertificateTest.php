<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\MedicalCertificate;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMedicalCertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-cert@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Cert Patient', 'phone' => '0500009001', 'gender' => 'male',
        ]);
        $this->patient->file_number = 'PAT-CERT-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور شهادات', 'name_en' => 'Cert Doctor',
            'specialization_ar' => 'جلدية', 'specialization_en' => 'Dermatology',
            'department' => 'derma', 'status' => 'active',
        ]);
    }

    public function test_can_view_certificates_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/medical-certificates')->assertOk();
    }

    public function test_can_create_certificate(): void
    {
        $this->actingAs($this->admin)->post('/admin/medical-certificates', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'sick_leave',
            'issue_date' => '2025-06-01',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-03',
            'diagnosis' => 'Flu',
            'notes' => 'Rest recommended',
        ])->assertRedirect();

        $this->assertDatabaseHas('medical_certificates', [
            'patient_id' => $this->patient->id,
            'type' => 'sick_leave',
            'status' => 'draft',
            'days' => 3,
        ]);
    }

    public function test_certificate_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/medical-certificates', [])
            ->assertSessionHasErrors(['patient_id', 'doctor_id', 'type', 'issue_date']);
    }

    public function test_type_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/admin/medical-certificates', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'invalid_type',
            'issue_date' => '2025-06-01',
        ])->assertSessionHasErrors('type');
    }

    public function test_can_issue_draft_certificate(): void
    {
        $cert = MedicalCertificate::create([
            'certificate_number' => 'MC-TEST-0001',
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'sick_leave',
            'issue_date' => '2025-06-01',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/medical-certificates/{$cert->id}/issue")
            ->assertRedirect();

        $cert->refresh();
        $this->assertEquals('issued', $cert->status);
        $this->assertNotNull($cert->issued_at);
    }

    public function test_cannot_issue_non_draft_certificate(): void
    {
        $cert = MedicalCertificate::create([
            'certificate_number' => 'MC-TEST-0002',
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'fitness',
            'issue_date' => '2025-06-01',
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/medical-certificates/{$cert->id}/issue")
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_can_cancel_certificate(): void
    {
        $cert = MedicalCertificate::create([
            'certificate_number' => 'MC-TEST-0003',
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'medical_report',
            'issue_date' => '2025-06-01',
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/medical-certificates/{$cert->id}/cancel")
            ->assertRedirect();

        $cert->refresh();
        $this->assertEquals('cancelled', $cert->status);
    }

    public function test_cannot_cancel_already_cancelled(): void
    {
        $cert = MedicalCertificate::create([
            'certificate_number' => 'MC-TEST-0004',
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'referral_letter',
            'issue_date' => '2025-06-01',
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/medical-certificates/{$cert->id}/cancel")
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_generates_certificate_number(): void
    {
        $this->actingAs($this->admin)->post('/admin/medical-certificates', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'follow_up',
            'issue_date' => '2025-06-01',
        ])->assertRedirect();

        $cert = MedicalCertificate::first();
        $this->assertStringStartsWith('MC-', $cert->certificate_number);
    }
}

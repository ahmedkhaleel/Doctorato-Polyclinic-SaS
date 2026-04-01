<?php

namespace Tests\Unit\Policies;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Policies\PatientPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PatientPolicy $policy;
    private User $admin;
    private User $doctorUser;
    private User $patientUser;
    private User $secretaryUser;
    private Patient $patient;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PatientPolicy();

        // Admin with full permissions
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password',
            'role_id' => $adminRole->id, 'is_active' => true,
        ]);

        // Secretary with specific permissions
        $secretaryRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتارية',
             'permissions' => ['patients.view', 'patients.create', 'patients.update'], 'is_system' => true]
        );
        $this->secretaryUser = User::create([
            'name' => 'Secretary', 'email' => 'secretary@test.com', 'password' => 'password',
            'role_id' => $secretaryRole->id, 'is_active' => true,
        ]);

        // Doctor
        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['doctor.*'], 'is_system' => true]
        );
        $this->doctorUser = User::create([
            'name' => 'Doctor', 'email' => 'doctor@test.com', 'password' => 'password',
            'role_id' => $doctorRole->id, 'is_active' => true,
        ]);
        $this->doctor = Doctor::create([
            'name_en' => 'Dr. Test', 'name_ar' => 'د. تجربة', 'status' => 'active',
            'user_id' => $this->doctorUser->id, 'default_commission_percentage' => 30,
        ]);

        // Patient
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
    }

    // ─── viewAny ────────────────────────────────────────

    public function test_admin_can_view_any_patients(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_secretary_can_view_any_patients(): void
    {
        $this->assertTrue($this->policy->viewAny($this->secretaryUser));
    }

    public function test_patient_cannot_view_any_patients(): void
    {
        $this->assertFalse($this->policy->viewAny($this->patientUser));
    }

    // ─── view ───────────────────────────────────────────

    public function test_admin_can_view_any_patient(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->patient));
    }

    public function test_patient_can_view_own_profile(): void
    {
        $this->assertTrue($this->policy->view($this->patientUser, $this->patient));
    }

    public function test_patient_cannot_view_other_patient(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other', 'phone' => '0501111111']);
        $otherPatient->file_number = Patient::generateFileNumber();
        $otherPatient->is_active = true;
        $otherPatient->save();

        $this->assertFalse($this->policy->view($this->patientUser, $otherPatient));
    }

    public function test_doctor_can_view_patient_with_visit(): void
    {
        // Create a booking and visit linking doctor to patient
        $booking = Booking::create([
            'booking_number' => 'BK-' . uniqid(), 'source' => 'clinic', 'module' => 'derma',
            'booking_type' => 'dermatology_consultation', 'status' => 'in_progress',
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'patient_id' => $this->patient->id,
        ]);

        Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id, 'visit_type' => 'consultation',
            'status' => 'completed', 'visit_date' => today(),
        ]);

        $this->assertTrue($this->policy->view($this->doctorUser, $this->patient));
    }

    public function test_doctor_cannot_view_patient_without_visit(): void
    {
        $otherPatient = new Patient(['full_name' => 'No Visit Patient', 'phone' => '0502222222']);
        $otherPatient->file_number = Patient::generateFileNumber();
        $otherPatient->is_active = true;
        $otherPatient->save();

        $this->assertFalse($this->policy->view($this->doctorUser, $otherPatient));
    }

    // ─── create ─────────────────────────────────────────

    public function test_admin_can_create_patient(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_patient_cannot_create_patient(): void
    {
        $this->assertFalse($this->policy->create($this->patientUser));
    }

    // ─── update ─────────────────────────────────────────

    public function test_admin_can_update_any_patient(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->patient));
    }

    public function test_patient_can_update_own_profile(): void
    {
        $this->assertTrue($this->policy->update($this->patientUser, $this->patient));
    }

    // ─── delete ─────────────────────────────────────────

    public function test_admin_can_delete_patient(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->patient));
    }

    public function test_secretary_cannot_delete_patient(): void
    {
        $this->assertFalse($this->policy->delete($this->secretaryUser, $this->patient));
    }

    // ─── viewSensitive ──────────────────────────────────

    public function test_admin_can_view_sensitive_data(): void
    {
        $this->assertTrue($this->policy->viewSensitive($this->admin, $this->patient));
    }

    public function test_secretary_without_permission_cannot_view_sensitive(): void
    {
        $this->assertFalse($this->policy->viewSensitive($this->secretaryUser, $this->patient));
    }
}

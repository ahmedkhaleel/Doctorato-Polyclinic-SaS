<?php

namespace Tests\Unit\Policies;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Policies\VisitPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitPolicyTest extends TestCase
{
    use RefreshDatabase;

    private VisitPolicy $policy;
    private User $admin;
    private User $doctorUser;
    private User $patientUser;
    private Doctor $doctor;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new VisitPolicy();

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password',
            'role_id' => $adminRole->id, 'is_active' => true,
        ]);

        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );
        $this->doctorUser = User::create([
            'name' => 'Doctor', 'email' => 'doctor@test.com', 'password' => 'password',
            'role_id' => $doctorRole->id, 'is_active' => true,
        ]);
        $this->doctor = Doctor::create([
            'name_en' => 'Dr. Test', 'name_ar' => 'د. تجربة', 'status' => 'active',
            'user_id' => $this->doctorUser->id, 'default_commission_percentage' => 30,
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

        $booking = Booking::create([
            'booking_number' => 'BK-' . uniqid(), 'source' => 'clinic', 'module' => 'derma',
            'booking_type' => 'dermatology_consultation', 'status' => 'in_progress',
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'patient_id' => $this->patient->id,
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id, 'visit_type' => 'consultation',
            'status' => 'in_progress', 'visit_date' => today(),
        ]);
    }

    public function test_admin_can_view_any_visit(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->visit));
    }

    public function test_doctor_can_view_own_visit(): void
    {
        $this->assertTrue($this->policy->view($this->doctorUser, $this->visit));
    }

    public function test_patient_can_view_own_visit(): void
    {
        $this->assertTrue($this->policy->view($this->patientUser, $this->visit));
    }

    public function test_doctor_can_update_own_visit(): void
    {
        $this->assertTrue($this->policy->update($this->doctorUser, $this->visit));
    }

    public function test_doctor_cannot_update_cancelled_visit(): void
    {
        $this->visit->update(['status' => 'cancelled']);

        $this->assertFalse($this->policy->update($this->doctorUser, $this->visit));
    }

    public function test_doctor_can_complete_own_in_progress_visit(): void
    {
        $this->assertTrue($this->policy->complete($this->doctorUser, $this->visit));
    }

    public function test_doctor_cannot_complete_cancelled_visit(): void
    {
        $this->visit->update(['status' => 'cancelled']);

        $this->assertFalse($this->policy->complete($this->doctorUser, $this->visit));
    }

    public function test_patient_cannot_update_visit(): void
    {
        $this->assertFalse($this->policy->update($this->patientUser, $this->visit));
    }

    public function test_admin_can_delete_visit(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->visit));
    }

    public function test_doctor_cannot_delete_visit(): void
    {
        $this->assertFalse($this->policy->delete($this->doctorUser, $this->visit));
    }
}

<?php

namespace Tests\Unit\Policies;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Policies\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private BookingPolicy $policy;
    private User $admin;
    private User $doctorUser;
    private User $patientUser;
    private Doctor $doctor;
    private Patient $patient;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new BookingPolicy();

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

        $this->booking = Booking::create([
            'booking_number' => 'BK-' . uniqid(), 'source' => 'website', 'module' => 'derma',
            'booking_type' => 'dermatology_consultation', 'status' => 'confirmed',
            'full_name' => 'Test Patient', 'phone' => '0500000000',
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
        ]);
    }

    public function test_admin_can_view_any_booking(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->booking));
    }

    public function test_doctor_can_view_own_booking(): void
    {
        $this->assertTrue($this->policy->view($this->doctorUser, $this->booking));
    }

    public function test_doctor_cannot_view_other_doctors_booking(): void
    {
        $otherDoctor = Doctor::create([
            'name_en' => 'Other', 'name_ar' => 'آخر', 'status' => 'active',
            'default_commission_percentage' => 20,
        ]);
        $otherBooking = Booking::create([
            'booking_number' => 'BK-' . uniqid(), 'source' => 'website', 'module' => 'derma',
            'booking_type' => 'service', 'status' => 'confirmed',
            'full_name' => 'Other', 'phone' => '0509999999',
            'doctor_id' => $otherDoctor->id,
        ]);

        $this->assertFalse($this->policy->view($this->doctorUser, $otherBooking));
    }

    public function test_patient_can_view_own_booking(): void
    {
        $this->assertTrue($this->policy->view($this->patientUser, $this->booking));
    }

    public function test_patient_cannot_view_others_booking(): void
    {
        $otherBooking = Booking::create([
            'booking_number' => 'BK-' . uniqid(), 'source' => 'website', 'module' => 'derma',
            'booking_type' => 'service', 'status' => 'confirmed',
            'full_name' => 'Other', 'phone' => '0509999999',
        ]);

        $this->assertFalse($this->policy->view($this->patientUser, $otherBooking));
    }

    public function test_admin_can_create_booking(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_patient_cannot_create_booking(): void
    {
        $this->assertFalse($this->policy->create($this->patientUser));
    }

    public function test_admin_can_delete_booking(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->booking));
    }

    public function test_admin_can_edit_services(): void
    {
        $this->assertTrue($this->policy->editServices($this->admin, $this->booking));
    }

    public function test_patient_cannot_edit_services(): void
    {
        $this->assertFalse($this->policy->editServices($this->patientUser, $this->booking));
    }
}

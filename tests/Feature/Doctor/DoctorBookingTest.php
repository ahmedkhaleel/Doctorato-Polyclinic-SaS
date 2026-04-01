<?php

namespace Tests\Feature\Doctor;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Booking Doctor', 'email' => 'doc-booking@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور حجز', 'name_en' => 'Booking Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Booking Patient', 'phone' => '01777000111']);
        $this->patient->file_number = 'P-BOOK-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    public function test_can_view_bookings_index(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/bookings')->assertOk();
    }

    public function test_can_view_own_bookings(): void
    {
        Booking::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_date' => now()->addDay()->toDateString(),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->doctorUser)->get('/doctor/bookings');
        $response->assertOk();
    }

    public function test_non_doctor_cannot_access_bookings(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'pat-docbook@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/doctor/bookings');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}

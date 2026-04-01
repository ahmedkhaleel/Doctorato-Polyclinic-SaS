<?php

namespace Tests\Feature\Doctor;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorPrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            [
                'display_name_en' => 'Doctor',
                'display_name_ar' => 'طبيب',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        $this->doctorUser = User::create([
            'name' => 'Doctor',
            'email' => 'doctor@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور تست',
            'name_en' => 'Test Doctor',
            'user_id' => $this->doctorUser->id,
            'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01000000003']);
        $this->patient->file_number = 'P-TEST-003';
        $this->patient->is_active = true;
        $this->patient->save();

        // Create a booking to satisfy Visit::creating hook
        $booking = Booking::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'confirmed',
            'booking_type' => 'dermatology_consultation',
            'module' => 'derma',
            'source' => 'secretary',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation',
            'module' => 'derma',
            'status' => 'in_progress',
            'visit_date' => now()->toDateString(),
        ]);
    }

    public function test_doctor_can_create_prescription_for_own_patient(): void
    {
        $this->actingAs($this->doctorUser);

        $response = $this->post('/doctor/prescriptions', [
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'diagnosis' => 'Acne vulgaris',
            'items' => [
                [
                    'medication_name' => 'Tretinoin 0.025%',
                    'dosage' => 'Apply thin layer',
                    'frequency' => 'Once daily at bedtime',
                    'duration' => '3 months',
                    'instructions' => 'Apply to affected areas only',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prescriptions', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
        ]);
    }

    public function test_doctor_cannot_prescribe_for_another_doctors_patient(): void
    {
        // Create a different doctor's patient
        $otherDoctorRole = Role::where('name', 'doctor')->first();
        $otherDoctorUser = User::create([
            'name' => 'Other Doctor',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role_id' => $otherDoctorRole->id,
            'is_active' => true,
        ]);
        $otherDoctor = Doctor::create([
            'name_ar' => 'دكتور آخر',
            'name_en' => 'Other Doctor',
            'user_id' => $otherDoctorUser->id,
            'status' => 'active',
        ]);

        $otherPatient = new Patient(['full_name' => 'Other Patient', 'phone' => '01000000099']);
        $otherPatient->file_number = 'P-OTHER';
        $otherPatient->is_active = true;
        $otherPatient->save();

        // This patient has NO visits with the first doctor
        $this->actingAs($this->doctorUser);

        $response = $this->post('/doctor/prescriptions', [
            'patient_id' => $otherPatient->id,
            'items' => [
                ['medication_name' => 'Test Med'],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_doctor_cannot_link_prescription_to_another_doctors_visit(): void
    {
        $otherDoctorRole = Role::where('name', 'doctor')->first();
        $otherDoctorUser = User::create([
            'name' => 'Other Doctor',
            'email' => 'other2@test.com',
            'password' => bcrypt('password'),
            'role_id' => $otherDoctorRole->id,
            'is_active' => true,
        ]);
        $otherDoctor = Doctor::create([
            'name_ar' => 'دكتور آخر',
            'name_en' => 'Other Doctor',
            'user_id' => $otherDoctorUser->id,
            'status' => 'active',
        ]);

        // Create a visit by another doctor for the same patient
        $booking2 = Booking::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoctor->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '14:30',
            'status' => 'confirmed',
            'booking_type' => 'dermatology_consultation',
            'module' => 'derma',
            'source' => 'secretary',
        ]);

        $otherVisit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoctor->id,
            'booking_id' => $booking2->id,
            'visit_type' => 'consultation',
            'module' => 'derma',
            'status' => 'in_progress',
            'visit_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->doctorUser);

        $response = $this->post('/doctor/prescriptions', [
            'patient_id' => $this->patient->id,
            'visit_id' => $otherVisit->id,
            'items' => [
                ['medication_name' => 'Test Med'],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_prescription_requires_at_least_one_item(): void
    {
        $this->actingAs($this->doctorUser);

        $response = $this->post('/doctor/prescriptions', [
            'patient_id' => $this->patient->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }
}

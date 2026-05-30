<?php

namespace Tests\Feature\Doctor;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorPatientTest extends TestCase
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
            'name' => 'Doctor', 'email' => 'doc-patient@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور', 'name_en' => 'Test Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01111111111']);
        $this->patient->file_number = 'P-DOC-001';
        $this->patient->is_active = true;
        $this->patient->save();

        // Create a booking + visit so doctor has this patient
        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '10:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);

        Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id, 'visit_type' => 'consultation',
            'module' => 'derma', 'status' => 'completed', 'visit_date' => now()->toDateString(),
        ]);
    }

    public function test_doctor_can_view_patients_index(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/patients')
            ->assertOk();
    }

    public function test_doctor_can_view_own_patient(): void
    {
        $this->actingAs($this->doctorUser)
            ->get("/doctor/patients/{$this->patient->id}")
            ->assertOk();
    }

    public function test_doctor_cannot_view_other_doctors_patient(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other Patient', 'phone' => '02222222222']);
        $otherPatient->file_number = 'P-DOC-002';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $this->actingAs($this->doctorUser)
            ->get("/doctor/patients/{$otherPatient->id}")
            ->assertForbidden();
    }

    public function test_doctor_can_update_notes_on_own_patient(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/patients/{$this->patient->id}/notes", [
                'doctor_notes' => 'Sensitive to retinoids. Monitor for dryness.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'id' => $this->patient->id,
            'doctor_notes' => 'Sensitive to retinoids. Monitor for dryness.',
        ]);
    }

    public function test_doctor_cannot_update_notes_on_other_patient(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other Patient', 'phone' => '03333333333']);
        $otherPatient->file_number = 'P-DOC-003';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $this->actingAs($this->doctorUser)
            ->post("/doctor/patients/{$otherPatient->id}/notes", [
                'doctor_notes' => 'Should not save.',
            ])
            ->assertForbidden();
    }

    public function test_doctor_notes_validates_max_length(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/patients/{$this->patient->id}/notes", [
                'doctor_notes' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors('doctor_notes');
    }

    public function test_doctor_can_clear_notes(): void
    {
        $this->patient->update(['doctor_notes' => 'Old notes']);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/patients/{$this->patient->id}/notes", [
                'doctor_notes' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'id' => $this->patient->id,
            'doctor_notes' => null,
        ]);
    }

    public function test_patient_search_api_returns_results(): void
    {
        $this->actingAs($this->doctorUser)
            ->getJson('/doctor/api/patients?q=Test')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['full_name' => 'Test Patient']);
    }

    public function test_patient_search_api_requires_min_chars(): void
    {
        $this->actingAs($this->doctorUser)
            ->getJson('/doctor/api/patients?q=T')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_patient_search_api_only_returns_own_patients(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other Person', 'phone' => '04444444444']);
        $otherPatient->file_number = 'P-DOC-004';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $this->actingAs($this->doctorUser)
            ->getJson('/doctor/api/patients?q=Other')
            ->assertOk()
            ->assertJsonCount(0);
    }
}

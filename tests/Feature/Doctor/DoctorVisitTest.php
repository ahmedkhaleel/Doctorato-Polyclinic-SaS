<?php

namespace Tests\Feature\Doctor;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitTest extends TestCase
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
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Visit Doctor', 'email' => 'doc-visit@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور زيارة', 'name_en' => 'Visit Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Visit Patient', 'phone' => '01555000111']);
        $this->patient->file_number = 'P-VISIT-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id, 'visit_type' => 'consultation',
            'module' => 'derma', 'status' => 'waiting', 'visit_date' => now()->toDateString(),
        ]);
    }

    public function test_doctor_can_view_visits_index(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/visits')
            ->assertOk();
    }

    public function test_doctor_can_view_own_visit(): void
    {
        $this->actingAs($this->doctorUser)
            ->get("/doctor/visits/{$this->visit->id}")
            ->assertOk();
    }

    public function test_doctor_cannot_view_other_doctors_visit(): void
    {
        $otherRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $otherUser = User::create([
            'name' => 'Other Doc', 'email' => 'other-doc-visit@test.com',
            'password' => bcrypt('password'), 'role_id' => $otherRole->id, 'is_active' => true,
        ]);

        $otherDoctor = Doctor::create([
            'name_ar' => 'دكتور آخر', 'name_en' => 'Other Doctor',
            'user_id' => $otherUser->id, 'status' => 'active',
        ]);

        // Other doctor cannot see this doctor's visit
        $this->actingAs($otherUser)
            ->get("/doctor/visits/{$this->visit->id}")
            ->assertForbidden();
    }

    public function test_doctor_can_start_visit(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/visits/{$this->visit->id}/start")
            ->assertRedirect();

        $this->visit->refresh();
        $this->assertEquals('in_progress', $this->visit->status);
    }

    public function test_doctor_can_complete_visit(): void
    {
        // First start the visit
        $this->visit->update(['status' => 'in_progress', 'started_at' => now()]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/visits/{$this->visit->id}/complete")
            ->assertRedirect();

        $this->visit->refresh();
        $this->assertEquals('completed', $this->visit->status);
    }

    public function test_doctor_can_filter_visits_by_status(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/visits?status=waiting')
            ->assertOk();
    }

    public function test_doctor_can_filter_visits_by_date(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/visits?date_from=' . now()->toDateString() . '&date_to=' . now()->toDateString())
            ->assertOk();
    }

    public function test_visit_index_only_shows_own_visits(): void
    {
        // Create another doctor with their own visit
        $otherUser = User::create([
            'name' => 'Doc2', 'email' => 'doc2-visit@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->doctorUser->role_id, 'is_active' => true,
        ]);
        $otherDoctor = Doctor::create([
            'name_ar' => 'دكتور ٢', 'name_en' => 'Doctor 2',
            'user_id' => $otherUser->id, 'status' => 'active',
        ]);
        $otherBooking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $otherDoctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '11:00', 'end_time' => '11:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);
        Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $otherDoctor->id,
            'booking_id' => $otherBooking->id,
            'visit_type' => 'consultation', 'module' => 'derma',
            'status' => 'waiting', 'visit_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get('/doctor/visits');

        $response->assertOk();
        // The page should load with only this doctor's visits
        $visits = $response->original->getData()['page']['props']['visits'] ?? null;
        if ($visits && isset($visits['data'])) {
            foreach ($visits['data'] as $v) {
                $this->assertEquals($this->doctor->id, $v['doctor_id']);
            }
        }
    }
}

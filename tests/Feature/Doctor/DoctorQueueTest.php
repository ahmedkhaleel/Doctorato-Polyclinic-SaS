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

class DoctorQueueTest extends TestCase
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
            'name' => 'Queue Doctor', 'email' => 'doc-queue@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور قائمة', 'name_en' => 'Queue Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Queue Patient', 'phone' => '0500009111', 'gender' => 'male']);
        $this->patient->file_number = 'PAT-Q-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    private function createVisit(array $overrides = []): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => today()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);

        return Visit::create(array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation',
            'module' => 'derma',
            'status' => 'waiting',
            'visit_date' => today()->toDateString(),
        ], $overrides));
    }

    public function test_can_view_queue(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/queue')->assertOk();
    }

    public function test_queue_shows_today_stats(): void
    {
        $this->createVisit(['status' => 'waiting']);
        $this->createVisit(['status' => 'completed']);

        $response = $this->actingAs($this->doctorUser)->get('/doctor/queue');
        $response->assertOk();
    }

    public function test_queue_filters_by_status(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/queue?status=waiting')
            ->assertOk();
    }

    public function test_queue_filters_by_view(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/queue?view=upcoming')
            ->assertOk();
    }

    public function test_invalid_view_rejected(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/queue?view=invalid')
            ->assertSessionHasErrors('view');
    }
}

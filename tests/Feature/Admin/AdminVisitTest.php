<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVisitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name_en' => 'Super Admin', 'display_name_ar' => 'مدير عام', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-visit-test@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور زيارة', 'name_en' => 'Visit Doctor', 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Visit Patient', 'phone' => '01888000001']);
        $this->patient->file_number = 'P-VISIT-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    private function createVisitWithBooking(array $visitOverrides = []): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'dermatology_consultation',
            'status' => 'confirmed',
            'source' => 'admin',
        ]);

        $bookingService = BookingService::create([
            'booking_id' => $booking->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 1,
            'unit_price' => 200,
            'total_price' => 200,
            'status' => 'pending',
        ]);

        $appointment = BookingAppointment::create([
            'booking_id' => $booking->id,
            'booking_service_id' => $bookingService->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'scheduled',
        ]);

        return Visit::create(array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now()->toDateString(),
            'visit_type' => 'consultation',
            'status' => 'waiting',
            'module' => 'derma',
        ], $visitOverrides));
    }

    public function test_can_view_visits_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/visits')->assertOk();
    }

    public function test_can_view_today_queue(): void
    {
        $this->actingAs($this->admin)->get('/admin/visits/today-queue')->assertOk();
    }

    public function test_can_view_visit_show(): void
    {
        $visit = $this->createVisitWithBooking();

        $this->actingAs($this->admin)->get("/admin/visits/{$visit->id}")->assertOk();
    }

    public function test_can_start_visit(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'waiting']);

        $this->actingAs($this->admin)
            ->post("/admin/visits/{$visit->id}/start")
            ->assertRedirect();

        $visit->refresh();
        $this->assertEquals('in_progress', $visit->status);
    }

    public function test_can_complete_visit(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'in_progress']);

        $this->actingAs($this->admin)
            ->post("/admin/visits/{$visit->id}/complete")
            ->assertRedirect();

        $visit->refresh();
        $this->assertEquals('completed', $visit->status);
    }

    public function test_can_cancel_visit(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'waiting']);

        $this->actingAs($this->admin)
            ->post("/admin/visits/{$visit->id}/cancel")
            ->assertRedirect();

        $visit->refresh();
        $this->assertEquals('cancelled', $visit->status);
    }

    public function test_can_update_diagnosis(): void
    {
        $visit = $this->createVisitWithBooking(['status' => 'in_progress']);

        $this->actingAs($this->admin)
            ->put("/admin/visits/{$visit->id}/diagnosis", [
                'diagnosis' => 'Test diagnosis content',
                'doctor_notes' => 'Some doctor notes',
            ])->assertRedirect();

        $visit->refresh();
        $this->assertEquals('Test diagnosis content', $visit->diagnosis);
    }

    public function test_visits_index_filters_by_status(): void
    {
        $this->createVisitWithBooking(['status' => 'waiting']);

        $this->actingAs($this->admin)
            ->get('/admin/visits?status=waiting')
            ->assertOk();
    }

    public function test_visits_index_search(): void
    {
        $this->createVisitWithBooking();

        $this->actingAs($this->admin)
            ->get('/admin/visits?search=Visit+Patient')
            ->assertOk();
    }

    public function test_unauthenticated_cannot_view_visits(): void
    {
        $response = $this->get('/admin/visits');
        $this->assertContains($response->status(), [302, 401, 403, 404]);
    }
}

<?php

namespace Tests\Feature\Secretary;

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

class SecretaryVisitTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;
    private Patient $patient;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتارية', 'permissions' => [
                'patients.view', 'patients.create', 'patients.update',
                'visits.view', 'visits.create', 'visits.update',
                'invoices.view', 'invoices.create',
                'payments.view', 'payments.create',
            ], 'is_system' => true]
        );

        $this->secretary = User::create([
            'name' => 'Secretary', 'email' => 'sec-visit@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور سكرتارية', 'name_en' => 'Sec Doctor', 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Sec Visit Patient', 'phone' => '01777000001']);
        $this->patient->file_number = 'P-SV-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    private function createVisit(array $overrides = []): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'dermatology_consultation',
            'status' => 'confirmed',
            'source' => 'secretary',
        ]);

        $bs = BookingService::create([
            'booking_id' => $booking->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 1,
            'unit_price' => 200,
            'total_price' => 200,
            'status' => 'pending',
        ]);

        BookingAppointment::create([
            'booking_id' => $booking->id,
            'booking_service_id' => $bs->id,
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
            'visit_date' => now()->toDateString(),
            'visit_type' => 'consultation',
            'status' => 'waiting',
            'module' => 'derma',
        ], $overrides));
    }

    public function test_can_view_visits_index(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/visits')->assertOk();
    }

    public function test_can_view_visit_show(): void
    {
        $visit = $this->createVisit();
        $this->actingAs($this->secretary)->get("/secretary/visits/{$visit->id}")->assertOk();
    }

    public function test_can_start_visit(): void
    {
        $visit = $this->createVisit(['status' => 'waiting']);

        $this->actingAs($this->secretary)
            ->post("/secretary/visits/{$visit->id}/start")
            ->assertRedirect();

        $visit->refresh();
        $this->assertEquals('in_progress', $visit->status);
    }

    public function test_can_complete_visit(): void
    {
        $visit = $this->createVisit(['status' => 'in_progress']);

        $this->actingAs($this->secretary)
            ->post("/secretary/visits/{$visit->id}/complete")
            ->assertRedirect();

        $visit->refresh();
        $this->assertEquals('completed', $visit->status);
    }

    public function test_can_cancel_visit(): void
    {
        $visit = $this->createVisit(['status' => 'waiting']);

        $this->actingAs($this->secretary)
            ->post("/secretary/visits/{$visit->id}/cancel")
            ->assertRedirect();

        $visit->refresh();
        $this->assertEquals('cancelled', $visit->status);
    }

    public function test_unauthenticated_cannot_access(): void
    {
        $response = $this->get('/secretary/visits');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}

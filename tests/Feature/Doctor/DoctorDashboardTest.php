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

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Dashboard Doctor', 'email' => 'doc-dashboard@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور داشبورد', 'name_en' => 'Dashboard Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);
    }

    private function createVisitWithBooking(Patient $patient, Doctor $doctor, array $visitOverrides = []): Visit
    {
        $booking = Booking::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'full_name' => $patient->full_name, 'phone' => $patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '10:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);

        return Visit::create(array_merge([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'module' => 'derma',
            'status' => 'waiting', 'visit_date' => now()->toDateString(),
        ], $visitOverrides));
    }

    public function test_doctor_can_access_dashboard(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor')
            ->assertOk();
    }

    public function test_dashboard_shows_today_stats(): void
    {
        $patient = new Patient(['full_name' => 'Dash Patient', 'phone' => '01666000111']);
        $patient->file_number = 'P-DASH-001';
        $patient->is_active = true;
        $patient->save();

        $this->createVisitWithBooking($patient, $this->doctor, [
            'status' => 'completed', 'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get('/doctor');

        $response->assertOk();
        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertArrayHasKey('today', $props);
        $this->assertArrayHasKey('monthly', $props);
        $this->assertArrayHasKey('todayQueue', $props);
        $this->assertArrayHasKey('recentVisits', $props);
    }

    public function test_dashboard_has_payout_summary(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->get('/doctor');

        $response->assertOk();
        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertArrayHasKey('payoutSummary', $props);
        $this->assertArrayHasKey('total_paid', $props['payoutSummary']);
        $this->assertArrayHasKey('pending', $props['payoutSummary']);
    }

    public function test_dashboard_has_doctor_info(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->get('/doctor');

        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertArrayHasKey('doctorInfo', $props);
        $this->assertArrayHasKey('default_commission_percentage', $props['doctorInfo']);
    }

    public function test_dashboard_only_counts_own_visits(): void
    {
        $patient = new Patient(['full_name' => 'Shared Patient', 'phone' => '01666000222']);
        $patient->file_number = 'P-DASH-002';
        $patient->is_active = true;
        $patient->save();

        // Visit for this doctor
        $this->createVisitWithBooking($patient, $this->doctor, [
            'status' => 'completed', 'completed_at' => now(),
        ]);

        // Visit for another doctor
        $otherUser = User::create([
            'name' => 'Doc3', 'email' => 'doc3-dash@test.com',
            'password' => bcrypt('password'), 'role_id' => $this->doctorUser->role_id, 'is_active' => true,
        ]);
        $otherDoctor = Doctor::create([
            'name_ar' => 'دكتور ٣', 'name_en' => 'Doctor3',
            'user_id' => $otherUser->id, 'status' => 'active',
        ]);
        $this->createVisitWithBooking($patient, $otherDoctor, [
            'status' => 'completed', 'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get('/doctor');

        $props = $response->original->getData()['page']['props'] ?? [];
        // Today's completed should only be 1 (this doctor's visit), not 2
        $this->assertEquals(1, $props['today']['completed'] ?? $props['today']['completed_visits'] ?? 0);
    }

    public function test_dashboard_unauthenticated_denied(): void
    {
        $response = $this->get('/doctor');
        // Should redirect to login or return 401/403/404
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401, 403, 404]));
    }
}

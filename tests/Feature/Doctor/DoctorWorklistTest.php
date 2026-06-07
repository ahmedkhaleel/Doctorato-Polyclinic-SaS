<?php

namespace Tests\Feature\Doctor;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Services\DoctorWorklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorWorklistTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]);

        $this->doctorUser = User::create([
            'name' => 'WL Doc', 'email' => 'wl-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور', 'name_en' => 'WL Doc', 'user_id' => $this->doctorUser->id,
            'status' => 'active', 'module' => 'derma',
        ]);
        $this->patient = new Patient(['full_name' => 'WL Patient', 'phone' => '01999000111']);
        $this->patient->file_number = 'P-WL-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    private function completedVisitNoDiagnosis(): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);

        return Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'module' => 'derma', 'status' => 'completed',
            'visit_date' => now()->toDateString(), 'diagnosis' => null,
        ]);
    }

    public function test_buckets_aggregate_actionable_items(): void
    {
        $this->completedVisitNoDiagnosis();

        \App\Models\ObgynLabTest::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'test_type' => 'Hb', 'value' => '8.5', 'unit' => 'g/dL', 'reference_range' => '11-14',
            'result_date' => now()->toDateString(), 'is_abnormal' => true,
        ]);

        \App\Models\DermaTreatmentPlan::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'title_ar' => 'خطة', 'title_en' => 'Plan', 'session_type' => 'laser',
            'estimated_sessions' => 6, 'completed_sessions' => 2, 'interval_days' => 21, 'estimated_cost' => 2400,
            'status' => \App\Models\DermaTreatmentPlan::STATUS_IN_PROGRESS, 'start_date' => now()->subWeeks(3)->toDateString(),
        ]);

        $b = app(DoctorWorklistService::class)->buckets($this->doctor->id, true);

        $this->assertSame(1, $b['incomplete_docs']['count']);
        $this->assertSame(1, $b['results_review']['count']);
        $this->assertSame(1, $b['open_plans']['count']);
        $this->assertSame(3, $b['total']);
        $this->assertNotEmpty($b['incomplete_docs']['items']);
    }

    public function test_open_plan_excluded_when_patient_has_upcoming_booking(): void
    {
        \App\Models\DermaTreatmentPlan::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'title_ar' => 'خطة', 'title_en' => 'Plan', 'session_type' => 'laser',
            'estimated_sessions' => 6, 'completed_sessions' => 2, 'interval_days' => 21, 'estimated_cost' => 2400,
            'status' => \App\Models\DermaTreatmentPlan::STATUS_IN_PROGRESS, 'start_date' => now()->subWeeks(3)->toDateString(),
        ]);
        // Future booking for the same patient+doctor → plan no longer "open".
        Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'preferred_date' => now()->addWeek()->toDateString(), 'preferred_time' => '10:00',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation', 'module' => 'derma', 'source' => 'secretary',
        ]);

        $b = app(DoctorWorklistService::class)->buckets($this->doctor->id, true);
        $this->assertSame(0, $b['open_plans']['count']);
    }

    public function test_worklist_page_loads_and_dashboard_exposes_counts(): void
    {
        $this->completedVisitNoDiagnosis();

        $this->actingAs($this->doctorUser)->get('/doctor/worklist')->assertOk();

        $props = $this->actingAs($this->doctorUser)->get('/doctor')->assertOk()
            ->original->getData()['page']['props'];
        $this->assertArrayHasKey('worklistCounts', $props);
        $this->assertSame(1, $props['worklistCounts']['incomplete_docs']);
    }
}

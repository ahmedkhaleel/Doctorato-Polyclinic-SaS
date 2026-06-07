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
            ->get('/doctor/visits?date_from='.now()->toDateString().'&date_to='.now()->toDateString())
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

    public function test_derma_visit_show_exposes_specialty_panel_data(): void
    {
        $plan = \App\Models\DermaTreatmentPlan::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_ar' => 'خطة ليزر', 'title_en' => 'Laser Plan',
            'session_type' => 'laser',
            'estimated_sessions' => 6, 'completed_sessions' => 2,
            'interval_days' => 21, 'estimated_cost' => 2400,
            'status' => \App\Models\DermaTreatmentPlan::STATUS_IN_PROGRESS,
            'start_date' => now()->subWeeks(3)->toDateString(),
        ]);

        \App\Models\DermaSession::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'visit_id' => $this->visit->id,
            'treatment_plan_id' => $plan->id,
            'session_type' => 'laser', 'area_treated' => 'face',
            'session_number' => 2, 'total_sessions' => 6, 'cost' => 400,
            'completed_at' => now()->toDateString(),
        ]);

        $props = $this->actingAs($this->doctorUser)
            ->get("/doctor/visits/{$this->visit->id}")
            ->assertOk()
            ->original->getData()['page']['props'];

        $this->assertNotNull($props['dermaActivePlan'] ?? null, 'dermaActivePlan present');
        $this->assertSame('laser', $props['dermaActivePlan']['session_type']);
        $this->assertSame(33, (int) $props['dermaActivePlan']['progress_percentage']); // 2/6
        $this->assertNotEmpty($props['dermaSessions'] ?? []);
        $this->assertSame($this->visit->id, $props['dermaSessions'][0]['visit_id']);
    }

    private function makeVisit(string $module, ?Doctor $doctor = null): Visit
    {
        $doctor ??= $this->doctor;
        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '12:00', 'end_time' => '12:30',
            'status' => 'confirmed', 'booking_type' => $module.'_consultation',
            'module' => $module, 'source' => 'secretary',
        ]);

        return Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $doctor->id,
            'booking_id' => $booking->id, 'visit_type' => 'consultation', 'module' => $module,
            'status' => 'waiting', 'visit_date' => now()->toDateString(),
        ]);
    }

    public function test_obgyn_visit_show_exposes_pregnancy_with_gestational_age(): void
    {
        $visit = $this->makeVisit('obgyn');

        $preg = \App\Models\Pregnancy::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'lmp' => now()->subWeeks(10)->toDateString(),
            'edd' => now()->addWeeks(30)->toDateString(), 'edd_source' => 'lmp',
            'gravida' => 2, 'para' => 1, 'conception_method' => 'natural',
            'is_high_risk' => true, 'risk_factors' => ['GDM'],
            'status' => \App\Models\Pregnancy::STATUS_ACTIVE,
        ]);
        \App\Models\ObgynLabTest::create([
            'patient_id' => $this->patient->id, 'pregnancy_id' => $preg->id, 'doctor_id' => $this->doctor->id,
            'test_type' => 'Hb', 'value' => '9.0', 'unit' => 'g/dL', 'reference_range' => '11-14',
            'result_date' => now()->toDateString(), 'is_abnormal' => true,
        ]);

        $props = $this->actingAs($this->doctorUser)
            ->get("/doctor/visits/{$visit->id}")->assertOk()
            ->original->getData()['page']['props'];

        $this->assertNotNull($props['obgynPregnancy'] ?? null);
        $this->assertSame(10, (int) $props['obgynPregnancy']['gestational_weeks']);
        $this->assertTrue((bool) $props['obgynPregnancy']['is_high_risk']);
        $this->assertNotEmpty($props['obgynLabTests'] ?? []);
        $this->assertTrue((bool) $props['obgynLabTests'][0]['is_abnormal']);
    }

    public function test_psychiatry_risk_is_locked_without_sensitive_permission(): void
    {
        $visit = $this->makeVisit('psychiatry');
        \App\Models\RiskAssessment::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'type' => 'suicide', 'tool' => 'c-ssrs', 'answers' => [],
            'risk_level' => 'high', 'is_active' => true, 'assessed_at' => now(),
        ]);
        \App\Models\MedicationPlan::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'module' => 'psychiatry', 'drug' => 'Sertraline', 'dose' => '50mg',
            'frequency' => 'OD', 'started_at' => now()->toDateString(), 'is_controlled' => false,
        ]);

        // Doctor role has no permissions → risk must be hidden, meds still visible.
        $props = $this->actingAs($this->doctorUser)
            ->get("/doctor/visits/{$visit->id}")->assertOk()
            ->original->getData()['page']['props'];

        $this->assertFalse((bool) ($props['neuroCanViewSensitive'] ?? false));
        $this->assertNull($props['neuroRisk'] ?? null, 'risk must be hidden without permission');
        $this->assertNotEmpty($props['neuroMeds'] ?? []);
    }

    public function test_psychiatry_risk_visible_and_audited_with_sensitive_permission(): void
    {
        // DoctorAuth requires role name 'doctor'; grant the sensitive permission to it.
        $role = Role::where('name', 'doctor')->first();
        $role->update(['permissions' => ['psychiatry.view_sensitive']]);
        $user = User::create([
            'name' => 'Sensitive Doc', 'email' => 'sens-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        $doctor = Doctor::create([
            'name_ar' => 'نفسي', 'name_en' => 'Psych', 'user_id' => $user->id,
            'status' => 'active', 'module' => 'psychiatry',
        ]);
        $visit = $this->makeVisit('psychiatry', $doctor);
        \App\Models\RiskAssessment::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $doctor->id,
            'type' => 'suicide', 'tool' => 'c-ssrs', 'answers' => [],
            'risk_level' => 'high', 'is_active' => true, 'assessed_at' => now(),
        ]);

        $props = $this->actingAs($user)
            ->get("/doctor/visits/{$visit->id}")->assertOk()
            ->original->getData()['page']['props'];

        $this->assertTrue((bool) $props['neuroCanViewSensitive']);
        $this->assertSame('high', $props['neuroRisk']['risk_level']);
        $this->assertDatabaseHas('medical_data_access_logs', [
            'patient_id' => $this->patient->id,
            'data_category' => 'neuropsych_risk',
        ]);
    }

    public function test_neurology_visit_exposes_seizure_and_headache_diaries(): void
    {
        $visit = $this->makeVisit('neurology');
        \App\Models\SeizureDiaryEntry::create([
            'patient_id' => $this->patient->id, 'occurred_at' => now()->subDays(5),
            'seizure_type' => 'focal', 'duration_seconds' => 90, 'entered_by' => 'doctor',
        ]);
        \App\Models\HeadacheDiaryEntry::create([
            'patient_id' => $this->patient->id, 'date' => now()->subDays(3)->toDateString(),
            'intensity' => 7, 'ichd3_type' => 'migraine', 'aura' => true, 'entered_by' => 'doctor',
        ]);

        $props = $this->actingAs($this->doctorUser)
            ->get("/doctor/visits/{$visit->id}")->assertOk()
            ->original->getData()['page']['props'];

        $this->assertNotEmpty($props['neuroSeizures'] ?? []);
        $this->assertNotEmpty($props['neuroHeadaches'] ?? []);
        $this->assertSame(7, (int) $props['neuroHeadaches'][0]['intensity']);
    }
}

<?php

namespace Tests\Feature\Physio;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PhysioAssessment;
use App\Models\PhysioSession;
use App\Models\PhysioTreatmentPlan;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * PT-3 — the physiotherapy visit panel is fed correct extras on both the doctor
 * and admin visit pages (active plan progress, sessions, and the latest
 * assessment's ROM/MMT/pain rows). The Vue panel + chart primitives are covered
 * by the build + InertiaPagesExistTest; here we assert the data contract.
 */
class PhysiotherapyVisitPanelTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private User $doctorUser;

    private Patient $patient;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $this->doctorUser = User::create(['name' => 'PT Visit Doc', 'email' => 'pt-visit@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT Visit Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'physiotherapy']);

        $this->patient = new Patient(['full_name' => 'PT Visit Patient', 'phone' => '01555222333']);
        $this->patient->file_number = 'P-PTV-1';
        $this->patient->is_active = true;
        $this->patient->save();

        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:45',
            'status' => 'confirmed', 'booking_type' => 'physiotherapy_session',
            'module' => 'physiotherapy', 'source' => 'secretary',
        ]);
        $this->visit = Visit::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id, 'visit_type' => 'consultation',
            'module' => 'physiotherapy', 'status' => 'waiting', 'visit_date' => now()->toDateString(),
        ]);

        // Seed a plan, an assessment (ROM/MMT/pain), and a session.
        PhysioTreatmentPlan::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'title_en' => 'Knee rehab', 'estimated_sessions' => 12, 'completed_sessions' => 4,
            'status' => 'in_progress', 'start_date' => now()->subWeeks(2)->toDateString(),
        ]);
        $assessment = PhysioAssessment::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'assessment_date' => now()->subWeek()->toDateString(), 'diagnosis' => 'Patellofemoral pain',
        ]);
        $assessment->romMeasurements()->create(['patient_id' => $this->patient->id, 'joint' => 'knee', 'motion' => 'flexion', 'side' => 'right', 'arom' => 100, 'normal_ref' => 135, 'recorded_at' => now()->subWeek()->toDateString()]);
        $assessment->strengthTests()->create(['patient_id' => $this->patient->id, 'muscle_group' => 'quadriceps', 'side' => 'right', 'grade' => 4, 'recorded_at' => now()->subWeek()->toDateString()]);
        $assessment->painPoints()->create(['patient_id' => $this->patient->id, 'view' => 'front', 'x' => 48, 'y' => 62, 'intensity' => 6, 'pain_type' => 'aching']);
        PhysioSession::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'session_number' => 4,
            'session_date' => now()->subDays(2)->toDateString(), 'attended' => true, 'pain_before' => 6, 'pain_after' => 3,
        ]);
    }

    public function test_doctor_visit_page_carries_physio_extras(): void
    {
        $this->actingAs($this->doctorUser)
            ->get("/doctor/visits/{$this->visit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Doctor/Visits/Show')
                ->where('physioActivePlan.progress_percentage', 33)
                ->where('physioActivePlan.completed_sessions', 4)
                ->has('physioSessions', 1)
                ->has('physioRom', 1)
                ->where('physioRom.0.normal_ref', '135.0')
                ->has('physioStrength', 1)
                ->has('physioPainPoints', 1)
            );
    }

    public function test_admin_visit_page_carries_physio_extras(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name_en' => 'Admin', 'display_name_ar' => 'مشرف', 'permissions' => ['*'], 'is_system' => true]);
        $admin = User::create(['name' => 'Admin', 'email' => 'pt-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $adminRole->id, 'is_active' => true]);

        $this->actingAs($admin)
            ->get("/admin/visits/{$this->visit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Visits/Show')
                ->where('physioActivePlan.completed_sessions', 4)
                ->has('physioRom', 1)
                ->has('physioStrength', 1)
                ->has('physioPainPoints', 1)
            );
    }
}

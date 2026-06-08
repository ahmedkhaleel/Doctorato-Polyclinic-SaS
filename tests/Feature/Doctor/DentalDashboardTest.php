<?php

namespace Tests\Feature\Doctor;

use App\Models\Booking;
use App\Models\DentalChart;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The dedicated dental cockpit (/doctor/dental) renders with KPIs, today's chair
 * queue (tooth/procedure), caseload condition + procedure mix, and resume plans.
 */
class DentalDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'D', 'email' => 'dent-dash@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'dental']);
        ModuleManager::enable('dental');
        ModuleManager::flushStaticCache();

        $patient = Patient::create(['full_name' => 'Dent P', 'phone' => '0500009090']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-DD-1'])->save();

        // A plan, a planned + a completed treatment, a chart tooth, and today's visit.
        DentalTreatmentPlan::create(['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'title_en' => 'Plan', 'estimated_sessions' => 4, 'completed_sessions' => 1, 'status' => 'in_progress']);
        DentalTreatment::create(['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'tooth_number' => 11, 'treatment_type' => 'filling', 'cost' => 300, 'status' => 'planned']);
        DentalTreatment::create(['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'tooth_number' => 12, 'treatment_type' => 'extraction', 'cost' => 200, 'status' => 'completed', 'completed_at' => now()]);
        DentalChart::create(['patient_id' => $patient->id, 'tooth_number' => 11, 'condition' => 'decayed', 'status' => 'active']);

        $booking = Booking::create(['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'full_name' => $patient->full_name, 'phone' => $patient->phone, 'booking_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30', 'status' => 'confirmed', 'booking_type' => 'dental_consultation', 'module' => 'dental', 'source' => 'secretary']);
        Visit::create(['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'booking_id' => $booking->id, 'visit_type' => 'consultation', 'module' => 'dental', 'status' => 'waiting', 'visit_date' => now()->toDateString(), 'scheduled_time' => '09:00']);
    }

    public function test_dental_dashboard_renders_with_cockpit_data(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/dental')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Doctor/Dental/Dashboard')
                ->where('stats.active_plans', 1)
                ->where('stats.pending_treatments', 1)
                ->where('stats.completed_today', 1)
                ->where('stats.production_today', 200)
                ->has('todayQueue', 1)
                ->where('todayQueue.0.next_procedure', 'filling')
                ->where('todayQueue.0.next_tooth', 11)
                ->has('conditionMix')
                ->has('procedureMix')
                ->has('resumePlans', 1)
            );
    }
}

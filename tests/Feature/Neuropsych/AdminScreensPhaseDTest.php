<?php

namespace Tests\Feature\Neuropsych;

use App\Models\CourseSession;
use App\Models\DeliveryRecord;
use App\Models\Doctor;
use App\Models\NeuroProcedure;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\Role;
use App\Models\SeizureDiaryEntry;
use App\Models\TreatmentCourse;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase D — treatment courses + neuro tools (neurology) + OB/GYN deliveries.
 */
class AdminScreensPhaseDTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'phaseD-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        ModuleManager::enable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::enable('obgyn');
        ModuleManager::flushStaticCache();

        $this->doctor = Doctor::create(['name_ar' => 'د. تجربة', 'name_en' => 'Dr Test', 'status' => 'active', 'module' => 'psychiatry']);
        $this->patient = Patient::create(['full_name' => 'Course Patient', 'phone' => '0500004444']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PD-1'])->save();
    }

    public function test_courses_shows_progress_and_consent(): void
    {
        $course = TreatmentCourse::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'module' => 'psychiatry',
            'type' => 'rtms', 'planned_sessions' => 20, 'consent_required' => true, 'consent_signed_at' => now(), 'status' => 'active',
        ]);
        CourseSession::create(['treatment_course_id' => $course->id, 'patient_id' => $this->patient->id, 'session_number' => 1, 'performed_at' => now(), 'completed_at' => now()]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/courses')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Courses')
                ->has('courses.data', 1)
                ->where('courses.data.0.completed_sessions', 1)
                ->where('courses.data.0.consent_ok', true)
            );
    }

    public function test_neuro_tools_lists_procedures_and_engagement(): void
    {
        NeuroProcedure::create(['patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'type' => 'EEG', 'performed_at' => now(), 'cost' => 400]);
        SeizureDiaryEntry::create(['patient_id' => $this->patient->id, 'occurred_at' => now()->subDays(2), 'seizure_type' => 'focal', 'entered_by' => 'patient']);

        $this->actingAs($this->admin)->get('/admin/neurology/neuro')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Neuro')
                ->has('procedures.data', 1)
                ->where('engagement.seizure_entries', 1)
            );
    }

    public function test_deliveries_register_renders(): void
    {
        $preg = Pregnancy::create(['patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'lmp' => now()->subWeeks(39)->toDateString(), 'status' => 'delivered']);
        DeliveryRecord::create([
            'pregnancy_id' => $preg->id, 'doctor_id' => $this->doctor->id, 'delivery_date' => now()->toDateString(),
            'delivery_mode' => 'vaginal', 'outcome' => 'live birth', 'baby_weight_grams' => 3200, 'apgar_1' => 8, 'apgar_5' => 9,
        ]);

        $this->actingAs($this->admin)->get('/admin/obgyn/deliveries')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Obgyn/Deliveries')
                ->has('deliveries.data', 1)
                ->where('deliveries.data.0.delivery_mode', 'vaginal')
            );
    }
}

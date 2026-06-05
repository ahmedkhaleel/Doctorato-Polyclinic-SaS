<?php

namespace Tests\Feature\Neuropsych;

use App\Models\AntenatalVisit;
use App\Models\Doctor;
use App\Models\ObgynLabTest;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\Role;
use App\Models\ScaleResult;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase B — Outcomes (measurement-based care) for psych/neuro; ANC queue +
 * lab-test oversight for OB/GYN.
 */
class AdminScreensPhaseBTest extends TestCase
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
        $this->admin = User::create(['name' => 'Admin', 'email' => 'phaseB-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        ModuleManager::enable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::enable('obgyn');
        ModuleManager::flushStaticCache();

        $this->doctor = Doctor::create(['name_ar' => 'د. تجربة', 'name_en' => 'Dr Test', 'status' => 'active', 'module' => 'psychiatry']);
        $this->patient = Patient::create(['full_name' => 'Outcome Patient', 'phone' => '0500002222']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PB-1'])->save();
    }

    public function test_outcomes_summarizes_scales_and_lists_recent(): void
    {
        // Two PHQ-9 results for the same patient — improvement (20 → 5).
        ScaleResult::create(['patient_id' => $this->patient->id, 'scale_key' => 'phq9', 'answers' => [], 'score' => 20, 'severity' => 'severe', 'flag' => true, 'taken_at' => now()->subDays(30)]);
        ScaleResult::create(['patient_id' => $this->patient->id, 'scale_key' => 'phq9', 'answers' => [], 'score' => 5, 'severity' => 'mild', 'flag' => false, 'taken_at' => now()]);

        $this->actingAs($this->admin)->get('/admin/psychiatry/outcomes')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Neuropsych/Outcomes')
                ->where('module', 'psychiatry')
                ->has('recent.data', 2)
                ->where('summary.0.improved_pct', 100)
            );
    }

    public function test_neurology_outcomes_renders(): void
    {
        $this->actingAs($this->admin)->get('/admin/neurology/outcomes')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Neuropsych/Outcomes')->where('module', 'neurology'));
    }

    public function test_anc_queue_lists_overdue_follow_ups(): void
    {
        $preg = Pregnancy::create(['patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'lmp' => now()->subWeeks(20)->toDateString(), 'status' => 'active']);
        AntenatalVisit::create([
            'pregnancy_id' => $preg->id, 'doctor_id' => $this->doctor->id,
            'visit_date' => now()->subDays(20)->toDateString(),
            'next_visit_date' => now()->subDays(5)->toDateString(),   // overdue
        ]);

        $this->actingAs($this->admin)->get('/admin/obgyn/anc')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Obgyn/Anc')
                ->has('queue', 1)
                ->where('queue.0.overdue', true)
            );
    }

    public function test_labs_defaults_to_abnormal(): void
    {
        ObgynLabTest::create(['patient_id' => $this->patient->id, 'test_type' => 'Hb', 'value' => '8.1', 'unit' => 'g/dL', 'is_abnormal' => true, 'result_date' => now()->toDateString()]);
        ObgynLabTest::create(['patient_id' => $this->patient->id, 'test_type' => 'TSH', 'value' => '2.0', 'unit' => 'mIU/L', 'is_abnormal' => false, 'result_date' => now()->toDateString()]);

        $this->actingAs($this->admin)->get('/admin/obgyn/labs')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Obgyn/Labs')
                ->has('labs.data', 1)   // abnormal only by default
                ->where('labs.data.0.is_abnormal', true)
            );

        // All filter shows both.
        $this->actingAs($this->admin)->get('/admin/obgyn/labs?filter=all')
            ->assertInertia(fn (Assert $p) => $p->has('labs.data', 2));
    }
}

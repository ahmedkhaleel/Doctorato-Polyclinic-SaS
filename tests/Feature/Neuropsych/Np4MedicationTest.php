<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\MedicationMonitoring;
use App\Models\MedicationPlan;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP4 — medication plans auto-schedule the right safety monitoring, mirror
 * controlled drugs into the register, and ANC monitoring recurs weekly.
 */
class Np4MedicationTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'Doc', 'email' => 'np4-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'psychiatry']);

        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();

        $this->patient = Patient::create(['full_name' => 'Pat', 'phone' => '0500006666']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-NP4-1'])->save();
    }

    private function storePlan(array $extra = []): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/medications', array_merge([
            'patient_id' => $this->patient->id,
            'drug' => 'Drug',
            'drug_class' => 'other',
        ], $extra))->assertRedirect();
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/psychiatry/medications')->assertOk();
    }

    public function test_clozapine_schedules_weekly_anc(): void
    {
        $this->storePlan(['drug' => 'Clozapine', 'drug_class' => 'clozapine']);

        $plan = MedicationPlan::where('patient_id', $this->patient->id)->firstOrFail();
        $m = MedicationMonitoring::where('medication_plan_id', $plan->id)->where('type', 'clozapine_anc')->firstOrFail();
        $this->assertSame('due', $m->status);
        $this->assertSame(now()->addDays(7)->toDateString(), $m->due_at->toDateString());
    }

    public function test_lithium_and_antipsychotic_schedule_their_monitoring(): void
    {
        $this->storePlan(['drug' => 'Lithium', 'drug_class' => 'lithium']);
        $this->storePlan(['drug' => 'Risperidone', 'drug_class' => 'antipsychotic']);

        $this->assertDatabaseHas('medication_monitoring', ['type' => 'lithium_level', 'status' => 'due']);
        $this->assertDatabaseHas('medication_monitoring', ['type' => 'metabolic', 'status' => 'due']);
    }

    public function test_controlled_drug_writes_register_entry(): void
    {
        $this->storePlan(['drug' => 'Methylphenidate', 'drug_class' => 'stimulant', 'dose' => '10mg', 'is_controlled' => true]);

        $this->assertDatabaseHas('controlled_substance_register', [
            'patient_id' => $this->patient->id, 'drug' => 'Methylphenidate', 'quantity' => '10mg',
        ]);
    }

    public function test_recording_anc_result_schedules_next_week(): void
    {
        $this->storePlan(['drug' => 'Clozapine', 'drug_class' => 'clozapine']);
        $m = MedicationMonitoring::where('type', 'clozapine_anc')->firstOrFail();

        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/monitoring/{$m->id}/result", [
            'result' => 'ANC 4.2',
        ])->assertRedirect();

        $this->assertSame('done', $m->fresh()->status);
        // A fresh due ANC for next week exists.
        $this->assertSame(2, MedicationMonitoring::where('type', 'clozapine_anc')->count());
        $this->assertSame(1, MedicationMonitoring::where('type', 'clozapine_anc')->where('status', 'due')->count());
    }

    public function test_stop_sets_stopped_at(): void
    {
        $this->storePlan(['drug' => 'Sertraline', 'drug_class' => 'ssri']);
        $plan = MedicationPlan::where('patient_id', $this->patient->id)->firstOrFail();

        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/medications/{$plan->id}/stop")->assertRedirect();
        $this->assertNotNull($plan->fresh()->stopped_at);
    }
}

<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\HeadacheDiaryEntry;
use App\Models\NeuroProcedure;
use App\Models\Patient;
use App\Models\Role;
use App\Models\SeizureDiaryEntry;
use App\Models\Supply;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP5 — neurology procedures (botox bills + draws inventory) and the
 * seizure/headache diaries from both clinician and patient.
 */
class Np5NeuroTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $docRole = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $docRole->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'Doc', 'email' => 'np5-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $docRole->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'neurology']);

        $patRole = Role::firstOrCreate(['name' => 'patient'], ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $this->patientUser = User::create(['name' => 'Pat', 'email' => 'np5-pat@test.com', 'password' => bcrypt('x'), 'role_id' => $patRole->id, 'is_active' => true]);
        $this->patient = Patient::create(['full_name' => 'Pat', 'phone' => '0500007777']);
        $this->patient->forceFill(['user_id' => $this->patientUser->id, 'is_active' => true, 'file_number' => 'PAT-NP5-1'])->save();

        ModuleManager::enable('neurology');
        ModuleManager::flushStaticCache();
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/neurology/neuro')->assertOk();
    }

    public function test_botox_procedure_bills_and_draws_inventory(): void
    {
        $supply = Supply::create(['name_ar' => 'بوتوكس', 'name_en' => 'Botox', 'unit' => 'vial', 'quantity' => 5, 'min_quantity' => 1, 'purchase_price' => 300, 'is_active' => true]);

        $this->actingAs($this->doctorUser)->post('/doctor/neurology/neuro/procedures', [
            'patient_id' => $this->patient->id,
            'type' => 'botox',
            'performed_at' => now()->toDateString(),
            'cost' => 900,
            'supply_id' => $supply->id,
            'consumption_qty' => 2,
            'completed_at' => now()->toDateString(),
        ])->assertRedirect();

        $proc = NeuroProcedure::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertNotNull($proc->invoice_item_id);
        $this->assertDatabaseHas('invoice_items', ['id' => $proc->invoice_item_id, 'total' => 900]);
        $this->assertNotNull($proc->supply_transaction_id);
        $this->assertEquals(3.0, (float) $supply->fresh()->quantity); // 5 - 2
    }

    public function test_doctor_logs_seizure_and_headache(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/neurology/neuro/seizures', [
            'patient_id' => $this->patient->id, 'occurred_at' => now()->toDateTimeString(), 'seizure_type' => 'generalized_tonic_clonic',
        ])->assertRedirect();
        $this->actingAs($this->doctorUser)->post('/doctor/neurology/neuro/headaches', [
            'patient_id' => $this->patient->id, 'date' => now()->toDateString(), 'intensity' => 7, 'ichd3_type' => 'migraine',
        ])->assertRedirect();

        $this->assertDatabaseHas('seizure_diary', ['patient_id' => $this->patient->id, 'entered_by' => 'doctor']);
        $this->assertDatabaseHas('headache_diary', ['patient_id' => $this->patient->id, 'entered_by' => 'doctor', 'intensity' => 7]);
    }

    public function test_patient_logs_diaries_from_portal(): void
    {
        $this->actingAs($this->patientUser)->post('/ar/patient/neuropsych/diaries/seizure', [
            'occurred_at' => now()->toDateTimeString(), 'seizure_type' => 'absence',
        ])->assertRedirect();
        $this->actingAs($this->patientUser)->post('/ar/patient/neuropsych/diaries/headache', [
            'date' => now()->toDateString(), 'intensity' => 5,
        ])->assertRedirect();

        $this->assertSame('patient', SeizureDiaryEntry::where('patient_id', $this->patient->id)->first()->entered_by);
        $this->assertSame('patient', HeadacheDiaryEntry::where('patient_id', $this->patient->id)->first()->entered_by);
    }

    public function test_patient_diary_index_renders(): void
    {
        $this->actingAs($this->patientUser)->get('/ar/patient/neuropsych/diaries')->assertOk();
    }

    public function test_neuro_exam_is_recorded_and_upserts(): void
    {
        $enc = \App\Models\NeuropsychEncounter::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->patient->id ? \App\Models\Doctor::where('user_id', $this->doctorUser->id)->value('id') : null,
            'module' => 'neurology',
            'encounter_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->doctorUser)->post('/doctor/neurology/neuro/exam', [
            'neuropsych_encounter_id' => $enc->id,
            'cranial_nerves' => ['ii' => 'intact'],
            'gait' => 'normal',
            'romberg' => 'negative',
        ])->assertRedirect();

        $this->assertDatabaseHas('neuro_exams', ['neuropsych_encounter_id' => $enc->id, 'gait' => 'normal', 'romberg' => 'negative']);

        // Upsert: a second post updates the same row, not a duplicate.
        $this->actingAs($this->doctorUser)->post('/doctor/neurology/neuro/exam', [
            'neuropsych_encounter_id' => $enc->id, 'gait' => 'ataxic', 'romberg' => 'positive',
        ])->assertRedirect();

        $this->assertSame(1, \App\Models\NeuroExam::where('neuropsych_encounter_id', $enc->id)->count());
        $this->assertSame('ataxic', \App\Models\NeuroExam::where('neuropsych_encounter_id', $enc->id)->value('gait'));
    }
}

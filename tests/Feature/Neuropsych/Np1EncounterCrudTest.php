<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP1-ui — doctor encounter CRUD end-to-end: create (with MSE + diagnoses),
 * billing on completion, billing reversal on un-complete, delete, and the
 * module gate. Mirrors the obgyn doctor-flow tests.
 */
class Np1EncounterCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]
        );
        $role->update(['permissions' => ['*']]);

        $this->doctorUser = User::create([
            'name' => 'NP Doc', 'email' => 'np-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        Doctor::create([
            'name_ar' => 'دكتور نفسي', 'name_en' => 'Psych Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'psychiatry',
        ]);

        ModuleManager::enable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::flushStaticCache();

        $this->patient = new Patient(['full_name' => 'NP Patient', 'phone' => '0500007777', 'gender' => 'male']);
        $this->patient->file_number = 'PAT-NPC-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    public function test_index_renders_for_both_modules(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/psychiatry/encounters')->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/neurology/encounters')->assertOk();
    }

    public function test_store_creates_encounter_with_mse_and_diagnosis(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/encounters', [
            'patient_id' => $this->patient->id,
            'encounter_date' => now()->toDateString(),
            'note_format' => 'soap',
            'subjective' => 'Low mood 3 weeks',
            'mse' => ['mood' => 'depressed', 'insight' => 'fair'],
            'cost' => 0,
            'diagnoses' => [
                ['code_system' => 'dsm5', 'code' => 'F32.1', 'label' => 'MDD moderate', 'is_primary' => true],
            ],
        ])->assertRedirect();

        $enc = NeuropsychEncounter::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame('psychiatry', $enc->module);
        $this->assertSame('depressed', $enc->mse['mood']);
        $this->assertSame(1, $enc->diagnoses()->count());
        $this->assertNull($enc->invoice_id, 'not completed → not billed');
    }

    public function test_completed_encounter_with_fee_generates_invoice(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/encounters', [
            'patient_id' => $this->patient->id,
            'encounter_date' => now()->toDateString(),
            'note_format' => 'soap',
            'cost' => 300,
            'completed_at' => now()->toDateString(),
        ])->assertRedirect();

        $enc = NeuropsychEncounter::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertNotNull($enc->invoice_id);
        $this->assertNotNull($enc->invoice_item_id);
        $this->assertDatabaseHas('invoice_items', ['id' => $enc->invoice_item_id, 'total' => 300]);
    }

    public function test_uncompleting_reverses_billing(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/encounters', [
            'patient_id' => $this->patient->id,
            'encounter_date' => now()->toDateString(),
            'note_format' => 'soap', 'cost' => 300, 'completed_at' => now()->toDateString(),
        ])->assertRedirect();

        $enc = NeuropsychEncounter::where('patient_id', $this->patient->id)->firstOrFail();
        $itemId = $enc->invoice_item_id;
        $this->assertNotNull($itemId);

        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/encounters/{$enc->id}", [
            'patient_id' => $this->patient->id,
            'encounter_date' => now()->toDateString(),
            'note_format' => 'soap', 'cost' => 300, 'completed_at' => null,
        ])->assertRedirect();

        $enc->refresh();
        $this->assertNull($enc->invoice_id);
        $this->assertDatabaseMissing('invoice_items', ['id' => $itemId]);
    }

    public function test_delete_removes_encounter_and_reverses_billing(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/encounters', [
            'patient_id' => $this->patient->id,
            'encounter_date' => now()->toDateString(),
            'note_format' => 'soap', 'cost' => 200, 'completed_at' => now()->toDateString(),
        ])->assertRedirect();

        $enc = NeuropsychEncounter::where('patient_id', $this->patient->id)->firstOrFail();
        $itemId = $enc->invoice_item_id;

        $this->actingAs($this->doctorUser)->delete("/doctor/psychiatry/encounters/{$enc->id}")->assertRedirect();

        $this->assertSoftDeleted('neuropsych_encounters', ['id' => $enc->id]);
        $this->assertDatabaseMissing('invoice_items', ['id' => $itemId]);
    }

    public function test_module_gate_blocks_when_disabled(): void
    {
        ModuleManager::disable('neurology');
        ModuleManager::flushStaticCache();

        $resp = $this->actingAs($this->doctorUser)->get('/doctor/neurology/encounters');
        $this->assertContains($resp->status(), [403, 404, 302]);
    }
}

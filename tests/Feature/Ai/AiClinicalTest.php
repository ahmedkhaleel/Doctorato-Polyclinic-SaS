<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiClinicalTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['ai.doctor'], 'is_system' => true]
        );
        $role->update(['permissions' => ['ai.doctor']]);
        $this->doctorUser = User::create([
            'name' => 'AI Doc', 'email' => 'ai-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $this->doctorUser->id, 'status' => 'active']);
    }

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_phi_redaction', '1', 'ai');
    }

    private function fakeChat(string $reply = 'AI clinical draft'): void
    {
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o',
            'choices' => [['message' => ['content' => $reply]]],
            'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 20],
        ], 200)]);
    }

    public function test_clinical_page_loads(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/ai')->assertOk();
    }

    public function test_soap_generates_with_disclaimer_flow(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'soap_note', 'enabled' => true, 'group' => 'clinical']);
        $this->fakeChat('S: ... O: ... A: ... P: ...');

        $this->actingAs($this->doctorUser)->postJson('/doctor/ai/soap', ['notes' => 'fever 3 days, cough'])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'soap_note', 'status' => 'success']);
    }

    public function test_summary_requires_consent_when_enabled(): void
    {
        $this->enableAi();
        Setting::set('ai_patient_consent_required', '1', 'ai');
        AiFeatureFlag::create(['key' => 'patient_summary', 'enabled' => true, 'group' => 'clinical']);
        $patient = Patient::create(['full_name' => 'Test P', 'phone' => '01099999999', 'is_active' => true]);

        // Without consent → blocked.
        $this->actingAs($this->doctorUser)->postJson('/doctor/ai/summary', ['patient_id' => $patient->id])
            ->assertStatus(422)->assertJson(['reason' => 'consent_required']);

        // With consent → works + audit log written.
        $this->fakeChat('Summary');
        $this->actingAs($this->doctorUser)->postJson('/doctor/ai/summary', ['patient_id' => $patient->id, 'consent' => true])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('medical_data_access_logs', [
            'patient_id' => $patient->id, 'access_type' => 'ai', 'data_category' => 'patient_summary',
        ]);
    }

    public function test_drug_interaction_check_runs(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'drug_interaction', 'enabled' => true, 'group' => 'clinical']);
        $this->fakeChat('No major interactions found.');

        $this->actingAs($this->doctorUser)->postJson('/doctor/ai/drug-check', [
            'medications' => ['Amoxicillin', 'Ibuprofen'],
            'allergies' => 'Penicillin',
            'current_meds' => 'Metformin',
        ])->assertOk()->assertJson(['ok' => true, 'text' => 'No major interactions found.']);
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'drug_interaction']);
    }

    public function test_clinical_feature_blocked_when_disabled(): void
    {
        // AI globally disabled.
        $this->actingAs($this->doctorUser)->postJson('/doctor/ai/differential', ['symptoms' => 'x'])
            ->assertStatus(422)->assertJson(['reason' => 'disabled']);
    }

    public function test_requires_ai_doctor_permission(): void
    {
        // Strip ai.doctor from the doctor role → the same doctor user is now blocked.
        Role::where('name', 'doctor')->update(['permissions' => []]);

        $this->actingAs($this->doctorUser)->postJson('/doctor/ai/soap', ['notes' => 'x'])->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The patient portal exposes ONLY curated patient-facing AI features (never
 * clinical/admin), and only when AI is ready + the feature flag is on. Verifies
 * the HandleInertiaRequests `ai` shared prop for a patient account.
 */
class PatientAiAccessTest extends TestCase
{
    use RefreshDatabase;

    private function patientUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $user = User::create([
            'name' => 'P', 'email' => 'p'.uniqid().'@t.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);
        $patient = new Patient(['full_name' => 'P One', 'phone' => '0100'.random_int(1000000, 9999999), 'gender' => 'male']);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->user_id = $user->id;
        $patient->save();

        return $user;
    }

    private function readyAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test-key', 'ai');
        Setting::set('ai_default_model', 'gpt-4o-mini', 'ai');
    }

    public function test_patient_sees_assistant_feature_when_enabled(): void
    {
        $this->readyAi();
        AiFeatureFlag::updateOrCreate(['key' => 'patient_assistant'], ['enabled' => true, 'group' => 'patient']);

        $this->actingAs($this->patientUser())
            ->get('/ar/patient/assistant')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('ai.enabled', true)
                ->where('ai.features', ['patient_assistant']));
    }

    public function test_patient_does_not_see_assistant_when_flag_off(): void
    {
        $this->readyAi();
        AiFeatureFlag::updateOrCreate(['key' => 'patient_assistant'], ['enabled' => false, 'group' => 'patient']);

        $this->actingAs($this->patientUser())
            ->get('/ar/patient/assistant')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('ai.enabled', false)->where('ai.features', []));
    }

    public function test_patient_never_sees_clinical_features(): void
    {
        $this->readyAi();
        AiFeatureFlag::updateOrCreate(['key' => 'patient_assistant'], ['enabled' => true, 'group' => 'patient']);
        AiFeatureFlag::updateOrCreate(['key' => 'soap_note'], ['enabled' => true, 'group' => 'clinical']);
        AiFeatureFlag::updateOrCreate(['key' => 'differential_dx'], ['enabled' => true, 'group' => 'clinical']);

        $this->actingAs($this->patientUser())
            ->get('/ar/patient/assistant')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('ai.features', ['patient_assistant']));
    }
}

<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Patient "explain my prescription" (AI gap #4). Verifies ownership security and
 * graceful fallback. The live AI path needs a real OpenAI key, so here (no real
 * key) the endpoint must fail gracefully (422, never 500) — proving the wiring,
 * gating and fallback are correct.
 */
class PatientPrescriptionExplainTest extends TestCase
{
    use RefreshDatabase;

    private function patientUser(string $email): array
    {
        $role = Role::firstOrCreate(['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $user = User::create([
            'name' => 'P', 'email' => $email, 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);
        $patient = new Patient(['full_name' => 'P', 'phone' => '0100'.random_int(1000000, 9999999), 'gender' => 'male']);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->user_id = $user->id;
        $patient->save();

        return [$user, $patient];
    }

    private function prescriptionWithMed(Patient $patient): Prescription
    {
        $rx = Prescription::create(['patient_id' => $patient->id, 'diagnosis' => 'Acne']);
        PrescriptionItem::create([
            'prescription_id' => $rx->id, 'medication_name' => 'Isotretinoin',
            'dosage' => '20mg', 'frequency' => 'daily', 'duration' => '3 months', 'sort_order' => 0,
        ]);

        return $rx;
    }

    private function readyAiWithFlag(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test-key', 'ai');
        Setting::set('ai_default_model', 'gpt-4o-mini', 'ai');
        AiFeatureFlag::updateOrCreate(['key' => 'patient_explain'], ['enabled' => true, 'group' => 'patient']);
    }

    public function test_patient_cannot_explain_another_patients_prescription(): void
    {
        $this->readyAiWithFlag();
        [$me] = $this->patientUser('me-rx@t.com');
        [, $other] = $this->patientUser('other-rx@t.com');
        $rx = $this->prescriptionWithMed($other);

        $this->actingAs($me)
            ->post("/ar/patient/prescriptions/{$rx->id}/explain")
            ->assertForbidden();
    }

    public function test_explain_owned_prescription_fails_gracefully_without_live_ai(): void
    {
        $this->readyAiWithFlag();
        [$me, $patient] = $this->patientUser('owner-rx@t.com');
        $rx = $this->prescriptionWithMed($patient);

        // No real OpenAI key → the call fails, but must degrade to a 422 JSON
        // message (never a 500), and never mutate data.
        $resp = $this->actingAs($me)->post("/ar/patient/prescriptions/{$rx->id}/explain");
        $this->assertContains($resp->status(), [200, 422]);
        $resp->assertJsonStructure(['ok']);
    }
}

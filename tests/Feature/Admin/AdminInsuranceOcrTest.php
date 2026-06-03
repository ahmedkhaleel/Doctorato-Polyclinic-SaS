<?php

namespace Tests\Feature\Admin;

use App\Models\InsuranceCompany;
use App\Models\InsurancePlan;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Insurance add-form + AI card OCR (AI gap #3). Covers the store path (the form
 * was previously unreachable), the OCR endpoint's validation, and its graceful
 * fallback when no live AI key is configured (must be 422 JSON, never 500).
 */
class AdminInsuranceOcrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private InsuranceCompany $company;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::enable('insurance');

        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-ocr@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient(['full_name' => 'Ins Patient', 'phone' => '01299998888', 'gender' => 'male']);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->save();

        $this->company = InsuranceCompany::create(['name_en' => 'Acme', 'name_ar' => 'أكمي', 'code' => 'ACM', 'is_active' => true]);
        InsurancePlan::create(['insurance_company_id' => $this->company->id, 'name_en' => 'Gold', 'name_ar' => 'ذهبي', 'coverage_percentage' => 80, 'is_active' => true]);
    }

    public function test_index_passes_companies_with_plans(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/insurance/patient-insurances')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Insurance/PatientInsurances/Index')
                ->has('companiesWithPlans'));
    }

    public function test_admin_can_add_patient_insurance(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/patients/{$this->patient->id}/insurances", [
                'insurance_company_id' => $this->company->id,
                'member_id' => 'M-12345',
                'relationship' => 'self',
            ])->assertRedirect();

        $this->assertDatabaseHas('patient_insurances', [
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->company->id,
            'member_id' => 'M-12345',
        ]);
    }

    public function test_ocr_requires_an_image(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/admin/insurance/patient-insurances/ocr', [])
            ->assertStatus(422);
    }

    public function test_ocr_degrades_gracefully_without_live_ai(): void
    {
        $resp = $this->actingAs($this->admin)->post('/admin/insurance/patient-insurances/ocr', [
            'image' => UploadedFile::fake()->image('card.jpg', 600, 380),
        ]);

        // No real OpenAI key → AiUnavailableException → graceful 422 JSON, never 500.
        $resp->assertStatus(422)->assertJsonStructure(['ok', 'message']);
    }
}

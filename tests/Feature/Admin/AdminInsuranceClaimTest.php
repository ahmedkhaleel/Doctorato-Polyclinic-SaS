<?php

namespace Tests\Feature\Admin;

use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsurancePlan;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Insurance claims are financially critical (claim amounts, the
 * draft→submitted→approved→paid state machine, and the used_amount rollup on
 * the patient's policy) and were previously untested. Covers index, create,
 * the allowed/disallowed status transitions, and the paid-amount rollup.
 */
class AdminInsuranceClaimTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private PatientInsurance $policy;

    protected function setUp(): void
    {
        parent::setUp();

        ModuleManager::enable('insurance');

        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-claim@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient(['full_name' => 'Claim Patient', 'phone' => '01088887777', 'gender' => 'male']);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->save();

        $company = InsuranceCompany::create(['name_en' => 'Acme Health', 'name_ar' => 'أكمي', 'code' => 'ACM', 'is_active' => true]);
        $plan = InsurancePlan::create([
            'insurance_company_id' => $company->id, 'name_en' => 'Gold', 'name_ar' => 'ذهبي',
            'coverage_percentage' => 80, 'is_active' => true,
        ]);
        $this->policy = PatientInsurance::create([
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $company->id,
            'insurance_plan_id' => $plan->id,
            'member_id' => 'M-001', 'max_annual_limit' => 100000, 'used_amount' => 0,
            'start_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'is_active' => true,
        ]);
    }

    private function makeClaim(string $status = 'draft'): InsuranceClaim
    {
        return InsuranceClaim::create([
            'claim_number' => InsuranceClaim::generateClaimNumber(),
            'patient_insurance_id' => $this->policy->id,
            'patient_id' => $this->patient->id,
            'service_date' => now()->toDateString(),
            'services_description' => 'Consultation + lab',
            'total_amount' => 1000, 'covered_amount' => 800, 'patient_share' => 200,
            'status' => $status, 'created_by' => $this->admin->id,
        ]);
    }

    public function test_can_view_claims_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/insurance/claims')->assertOk();
    }

    public function test_can_create_claim(): void
    {
        $this->actingAs($this->admin)->post('/admin/insurance/claims', [
            'patient_insurance_id' => $this->policy->id,
            'patient_id' => $this->patient->id,
            'service_date' => now()->toDateString(),
            'services_description' => 'Dental filling',
            'total_amount' => 500,
            'covered_amount' => 400,
            'patient_share' => 100,
        ])->assertRedirect();

        $this->assertDatabaseHas('insurance_claims', [
            'patient_id' => $this->patient->id,
            'total_amount' => 500,
            'status' => 'draft',
        ]);
    }

    public function test_create_requires_amounts_and_policy(): void
    {
        $this->actingAs($this->admin)->post('/admin/insurance/claims', [
            'services_description' => 'x',
        ])->assertSessionHasErrors(['patient_insurance_id', 'patient_id', 'total_amount']);
    }

    public function test_valid_status_transition_draft_to_submitted(): void
    {
        $claim = $this->makeClaim('draft');

        $this->actingAs($this->admin)
            ->post("/admin/insurance/claims/{$claim->id}/status", ['status' => 'submitted'])
            ->assertRedirect();

        $this->assertDatabaseHas('insurance_claims', ['id' => $claim->id, 'status' => 'submitted']);
        $this->assertNotNull($claim->fresh()->submitted_at);
    }

    public function test_invalid_status_transition_is_blocked(): void
    {
        $claim = $this->makeClaim('draft');

        // draft → paid is not an allowed transition
        $this->actingAs($this->admin)
            ->post("/admin/insurance/claims/{$claim->id}/status", ['status' => 'paid'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('insurance_claims', ['id' => $claim->id, 'status' => 'draft']);
    }

    public function test_paying_a_claim_rolls_up_used_amount_on_policy(): void
    {
        $claim = $this->makeClaim('approved');

        $this->actingAs($this->admin)
            ->post("/admin/insurance/claims/{$claim->id}/status", [
                'status' => 'paid',
                'paid_amount' => 800,
            ])->assertRedirect();

        $this->assertDatabaseHas('insurance_claims', ['id' => $claim->id, 'status' => 'paid']);
        $this->assertEquals(800, (float) $this->policy->fresh()->used_amount);
    }
}

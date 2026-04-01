<?php

namespace Tests\Feature\Admin;

use App\Models\InsuranceCompany;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInsuranceCompanyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-ins@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_insurance_companies_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/insurance/companies')->assertOk();
    }

    public function test_can_create_insurance_company(): void
    {
        $this->actingAs($this->admin)->post('/admin/insurance/companies', [
            'name_ar' => 'تأمين الصحة',
            'name_en' => 'Health Insurance Co',
            'phone' => '01000000001',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('insurance_companies', ['name_en' => 'Health Insurance Co']);
    }

    public function test_insurance_company_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/insurance/companies', [
            'is_active' => true,
        ])->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_insurance_company(): void
    {
        $company = InsuranceCompany::create([
            'name_ar' => 'قديم', 'name_en' => 'Old Company', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->put("/admin/insurance/companies/{$company->id}", [
            'name_ar' => 'محدث',
            'name_en' => 'Updated Company',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('insurance_companies', ['id' => $company->id, 'name_en' => 'Updated Company']);
    }

    public function test_can_delete_insurance_company(): void
    {
        $company = InsuranceCompany::create([
            'name_ar' => 'حذف', 'name_en' => 'Delete Co', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->delete("/admin/insurance/companies/{$company->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('insurance_companies', ['id' => $company->id]);
    }

    public function test_insurance_code_must_be_unique(): void
    {
        InsuranceCompany::create([
            'name_ar' => 'أول', 'name_en' => 'First', 'code' => 'INS001', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/insurance/companies', [
            'name_ar' => 'ثاني',
            'name_en' => 'Second',
            'code' => 'INS001',
            'is_active' => true,
        ])->assertSessionHasErrors('code');
    }
}

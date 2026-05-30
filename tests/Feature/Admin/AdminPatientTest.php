<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPatientTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-pat@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_patients_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/patients')->assertOk();
    }

    public function test_can_create_patient(): void
    {
        $this->actingAs($this->admin)->post('/admin/patients', [
            'full_name' => 'Ahmad Khaleel',
            'phone' => '0500000001',
            'gender' => 'male',
        ])->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'full_name' => 'Ahmad Khaleel',
            'phone' => '0500000001',
            'is_active' => true,
        ]);
    }

    public function test_patient_gets_auto_file_number(): void
    {
        $this->actingAs($this->admin)->post('/admin/patients', [
            'full_name' => 'Auto Number',
            'phone' => '0500000002',
            'gender' => 'female',
        ])->assertRedirect();

        $patient = Patient::where('phone', '0500000002')->first();
        $this->assertNotNull($patient);
        $this->assertNotEmpty($patient->file_number);
    }

    public function test_patient_requires_name_phone_gender(): void
    {
        $this->actingAs($this->admin)->post('/admin/patients', [])
            ->assertSessionHasErrors(['full_name', 'phone', 'gender']);
    }

    public function test_phone_must_be_unique(): void
    {
        $patient = new Patient([
            'full_name' => 'Existing', 'phone' => '0500000003', 'gender' => 'male',
        ]);
        $patient->file_number = 'PAT-0001';
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->admin)->post('/admin/patients', [
            'full_name' => 'New Patient',
            'phone' => '0500000003',
            'gender' => 'male',
        ])->assertSessionHasErrors('phone');
    }

    public function test_gender_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/admin/patients', [
            'full_name' => 'Bad Gender',
            'phone' => '0500000004',
            'gender' => 'other',
        ])->assertSessionHasErrors('gender');
    }

    public function test_can_update_patient(): void
    {
        $patient = new Patient([
            'full_name' => 'Old Name', 'phone' => '0500000005', 'gender' => 'male',
        ]);
        $patient->file_number = 'PAT-0002';
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->admin)->post("/admin/patients/{$patient->id}/update", [
            'full_name' => 'Updated Name',
            'phone' => '0500000005',
            'gender' => 'female',
        ])->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'full_name' => 'Updated Name',
            'gender' => 'female',
        ]);
    }

    public function test_can_delete_patient(): void
    {
        $patient = new Patient([
            'full_name' => 'Delete Me', 'phone' => '0500000006', 'gender' => 'male',
        ]);
        $patient->file_number = 'PAT-0003';
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->admin)->post("/admin/patients/{$patient->id}/delete")->assertRedirect();
    }

    public function test_quick_create_returns_json(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/patients/quick-create', [
            'full_name' => 'Quick Patient',
            'phone' => '0500000007',
            'gender' => 'female',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['patient']);
        $this->assertDatabaseHas('patients', ['full_name' => 'Quick Patient']);
    }

    public function test_can_search_patients(): void
    {
        $patient = new Patient([
            'full_name' => 'Searchable Patient', 'phone' => '0500000008', 'gender' => 'male',
        ]);
        $patient->file_number = 'PAT-SEARCH';
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->admin)
            ->get('/admin/patients/search?q=Searchable')
            ->assertOk();
    }

    public function test_blood_type_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/admin/patients', [
            'full_name' => 'Blood Test',
            'phone' => '0500000009',
            'gender' => 'male',
            'blood_type' => 'X+',
        ])->assertSessionHasErrors('blood_type');
    }
}

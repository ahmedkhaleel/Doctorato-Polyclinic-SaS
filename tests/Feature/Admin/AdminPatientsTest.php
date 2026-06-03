<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPatientsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Admin',
                'display_name_ar' => 'مدير',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_patients_index(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/patients');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_create_patient_page(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/patients/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_patient(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/patients', [
            'full_name' => 'New Patient',
            'phone' => '01000000000',
            'gender' => 'male',
            'referral_source' => 'walk_in',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patients', [
            'full_name' => 'New Patient',
            'phone' => '01000000000',
        ]);
    }

    public function test_admin_can_view_patient_details(): void
    {
        $patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01234567890']);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->admin);
        $response = $this->get("/admin/patients/{$patient->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_patient(): void
    {
        $patient = new Patient(['full_name' => 'Old Name', 'phone' => '01234567890', 'gender' => 'male']);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->admin);

        $response = $this->post("/admin/patients/{$patient->id}/update", [
            'full_name' => 'Updated Name',
            'phone' => '01234567890',
            'gender' => 'male',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'full_name' => 'Updated Name',
        ]);
    }

    public function test_patient_requires_full_name(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/patients', [
            'phone' => '01000000000',
        ]);

        $response->assertSessionHasErrors('full_name');
    }

    public function test_patient_requires_phone(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/patients', [
            'full_name' => 'Test',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_admin_can_quick_create_patient(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/patients/quick-create', [
            'full_name' => 'Quick Patient',
            'phone' => '01111111111',
            'gender' => 'male',
        ]);

        $response->assertSuccessful(); // 200 or 201
        $this->assertDatabaseHas('patients', [
            'full_name' => 'Quick Patient',
        ]);
    }

    /** §2-أ CRUD protocol: Delete must soft-delete the row (deleted_at set). */
    public function test_admin_can_delete_patient_soft_delete(): void
    {
        $patient = new Patient(['full_name' => 'To Delete', 'phone' => '01999999999', 'gender' => 'male']);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->admin);

        $response = $this->post("/admin/patients/{$patient->id}/delete");
        $response->assertRedirect();

        // Soft-deleted: excluded from default queries but row still present with deleted_at.
        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
        $this->assertNull(Patient::find($patient->id));
    }

    /** §2-أ CRUD protocol: search filter narrows the index result set. */
    public function test_patients_index_search_filter_narrows_results(): void
    {
        $a = new Patient(['full_name' => 'Aisha Ahmed', 'phone' => '01211111111', 'gender' => 'female']);
        $a->file_number = Patient::generateFileNumber();
        $a->is_active = true;
        $a->save();

        $b = new Patient(['full_name' => 'Bilal Saad', 'phone' => '01222222222', 'gender' => 'male']);
        $b->file_number = Patient::generateFileNumber();
        $b->is_active = true;
        $b->save();

        $this->actingAs($this->admin);

        $response = $this->get('/admin/patients?search=Aisha');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Patients/Index')
            ->where('patients.total', 1)
            ->where('patients.data.0.full_name', 'Aisha Ahmed')
        );
    }
}

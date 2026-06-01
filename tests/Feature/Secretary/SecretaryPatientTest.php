<?php

namespace Tests\Feature\Secretary;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryPatientTest extends TestCase
{
    use RefreshDatabase;

    protected User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            [
                'display_name_en' => 'Secretary',
                'display_name_ar' => 'سكرتير',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        $this->secretary = User::create([
            'name' => 'Secretary User',
            'email' => 'secretary@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    // ─── Index ─────────────────────────────────────────

    public function test_secretary_can_view_patients_index(): void
    {
        $this->actingAs($this->secretary);

        $response = $this->get('/secretary/patients');
        $response->assertStatus(200);
    }

    // ─── Store ─────────────────────────────────────────

    public function test_secretary_can_create_patient(): void
    {
        $this->actingAs($this->secretary);

        $response = $this->post('/secretary/patients', [
            'full_name' => 'New Patient',
            'phone' => '01000000000',
            'gender' => 'male',
            'referral_source' => 'walk_in',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success'); // message is locale-aware (ar/en)

        $this->assertDatabaseHas('patients', [
            'full_name' => 'New Patient',
            'phone' => '01000000000',
        ]);

        $patient = Patient::where('phone', '01000000000')->first();
        $this->assertNotNull($patient->file_number);
        $this->assertTrue($patient->is_active);
    }

    // ─── Show ──────────────────────────────────────────

    public function test_secretary_can_view_patient_details(): void
    {
        $patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01234567890']);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        $this->actingAs($this->secretary);

        $response = $this->get("/secretary/patients/{$patient->id}");
        $response->assertStatus(200);
    }

    // ─── Validation ────────────────────────────────────

    public function test_patient_requires_full_name(): void
    {
        $this->actingAs($this->secretary);

        $response = $this->post('/secretary/patients', [
            'phone' => '01000000000',
            'gender' => 'female',
        ]);

        $response->assertSessionHasErrors('full_name');
    }

    public function test_patient_requires_phone(): void
    {
        $this->actingAs($this->secretary);

        $response = $this->post('/secretary/patients', [
            'full_name' => 'Test Patient',
            'gender' => 'female',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    // ─── Quick Store ───────────────────────────────────

    public function test_secretary_can_quick_store_patient(): void
    {
        $this->actingAs($this->secretary);

        $response = $this->postJson('/secretary/patients/quick-create', [
            'full_name' => 'Quick Patient',
            'phone' => '01111111111',
            'gender' => 'male',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'patient' => ['id', 'file_number', 'full_name', 'phone'],
        ]);

        $this->assertDatabaseHas('patients', [
            'full_name' => 'Quick Patient',
            'phone' => '01111111111',
        ]);
    }
}

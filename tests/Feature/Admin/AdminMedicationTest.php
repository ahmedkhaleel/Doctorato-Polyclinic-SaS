<?php

namespace Tests\Feature\Admin;

use App\Models\Medication;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMedicationTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-med@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_medications_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/medications')->assertOk();
    }

    public function test_can_create_medication(): void
    {
        $this->actingAs($this->admin)->post('/admin/medications', [
            'name' => 'Amoxicillin 500mg',
            'default_dosage' => '500mg',
            'default_frequency' => 'Three times daily',
            'default_duration' => '7 days',
            'category' => 'Antibiotics',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('medications', [
            'name' => 'Amoxicillin 500mg',
            'category' => 'Antibiotics',
        ]);
    }

    public function test_medication_requires_name(): void
    {
        $this->actingAs($this->admin)->post('/admin/medications', [])
            ->assertSessionHasErrors('name');
    }

    public function test_medication_name_must_be_unique(): void
    {
        Medication::create(['name' => 'Ibuprofen', 'is_active' => true]);

        $this->actingAs($this->admin)->post('/admin/medications', [
            'name' => 'Ibuprofen',
        ])->assertSessionHasErrors('name');
    }

    public function test_can_update_medication(): void
    {
        $med = Medication::create(['name' => 'Old Med', 'is_active' => true]);

        $this->actingAs($this->admin)->put("/admin/medications/{$med->id}", [
            'name' => 'Updated Med',
            'category' => 'Updated Category',
        ])->assertRedirect();

        $this->assertDatabaseHas('medications', ['id' => $med->id, 'name' => 'Updated Med']);
    }

    public function test_can_delete_medication(): void
    {
        $med = Medication::create(['name' => 'Delete Me', 'is_active' => true]);

        $this->actingAs($this->admin)->delete("/admin/medications/{$med->id}")->assertRedirect();
        $this->assertDatabaseMissing('medications', ['id' => $med->id]);
    }

    public function test_can_search_medications(): void
    {
        $this->actingAs($this->admin)->get('/admin/medications?search=test')->assertOk();
    }
}

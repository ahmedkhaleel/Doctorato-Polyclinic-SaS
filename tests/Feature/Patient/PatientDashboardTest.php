<?php

namespace Tests\Feature\Patient;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;
    private Patient $patient;
    private string $locale = 'ar';

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );

        $this->patientUser = User::create([
            'name' => 'Test Patient', 'email' => 'patient@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        // Ensure the Patient has user_id linking to the User
        $this->patient = new Patient([
            'full_name' => 'Test Patient', 'phone' => '01999000111',
        ]);
        $this->patient->file_number = 'P-PAT-001';
        $this->patient->is_active = true;
        $this->patient->user_id = $this->patientUser->id;
        $this->patient->save();

        // Preload role relationship for middleware check
        $this->patientUser->load('role', 'patient');
    }

    public function test_patient_can_view_dashboard(): void
    {
        $this->actingAs($this->patientUser)
            ->get("/{$this->locale}/patient")
            ->assertOk();
    }

    public function test_patient_can_view_bookings(): void
    {
        $this->actingAs($this->patientUser)
            ->get("/{$this->locale}/patient/bookings")
            ->assertOk();
    }

    public function test_patient_can_view_invoices(): void
    {
        $this->actingAs($this->patientUser)
            ->get("/{$this->locale}/patient/invoices")
            ->assertOk();
    }

    public function test_patient_can_view_visits(): void
    {
        $this->actingAs($this->patientUser)
            ->get("/{$this->locale}/patient/visits")
            ->assertOk();
    }

    public function test_patient_can_view_profile(): void
    {
        $this->actingAs($this->patientUser)
            ->get("/{$this->locale}/patient/profile")
            ->assertOk();
    }

    public function test_unauthenticated_user_redirected(): void
    {
        $this->get("/{$this->locale}/patient")->assertRedirect();
    }

    public function test_non_patient_cannot_access(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-patdash@test.com',
            'password' => bcrypt('password'), 'role_id' => $adminRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get("/{$this->locale}/patient");
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}

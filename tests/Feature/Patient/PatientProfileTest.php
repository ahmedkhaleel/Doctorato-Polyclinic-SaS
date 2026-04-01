<?php

namespace Tests\Feature\Patient;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );

        $this->user = User::create([
            'name' => 'Patient User',
            'email' => 'patient@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Patient User',
            'phone' => '01000000001',
            'email' => 'patient@test.com',
        ]);
        $this->patient->user_id = $this->user->id;
        $this->patient->file_number = 'P-TEST-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    public function test_email_uniqueness_validated_on_update(): void
    {
        // Create another patient with a different email
        $otherPatient = new Patient([
            'full_name' => 'Other',
            'phone' => '01000000002',
            'email' => 'taken@test.com',
        ]);
        $otherPatient->file_number = 'P-TEST-002';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $this->actingAs($this->user);

        $response = $this->post('/en/patient/profile', [
            'email' => 'taken@test.com', // already used by another patient
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_patient_can_update_own_email(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/en/patient/profile', [
            'email' => 'newemail@test.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->patient->refresh();
        $this->assertEquals('newemail@test.com', $this->patient->email);

        // User email should also sync
        $this->user->refresh();
        $this->assertEquals('newemail@test.com', $this->user->email);
    }

    public function test_patient_can_keep_same_email(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/en/patient/profile', [
            'email' => 'patient@test.com', // same email
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_password_change_requires_current_password(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/en/patient/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_change_works_with_correct_current(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/en/patient/profile/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}

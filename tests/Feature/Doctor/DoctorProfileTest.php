<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DoctorProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Profile Doctor', 'email' => 'doc-profile@test.com',
            'password' => Hash::make('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور ملف', 'name_en' => 'Profile Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);
    }

    public function test_can_view_profile(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/profile')->assertOk();
    }

    public function test_can_update_profile(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/profile/update', [
            'phone' => '0501234567',
            'email' => 'doctor@clinic.com',
            'bio_en' => 'Experienced dermatologist',
        ])->assertRedirect();

        $this->doctor->refresh();
        $this->assertEquals('0501234567', $this->doctor->phone);
    }

    public function test_can_change_password(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $this->doctorUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->doctorUser->password));
    }

    public function test_wrong_current_password_rejected(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }
}

<?php

namespace Tests\Feature\Secretary;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecretaryProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true]
        );

        $this->secretary = User::create([
            'name' => 'Test Secretary', 'email' => 'sec-profile@test.com',
            'password' => Hash::make('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_profile(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/profile')->assertOk();
    }

    public function test_can_update_profile(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
        ])->assertRedirect();

        $this->secretary->refresh();
        $this->assertEquals('Updated Name', $this->secretary->name);
        $this->assertEquals('updated@test.com', $this->secretary->email);
    }

    public function test_profile_requires_name_and_email(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/profile/update', [])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_can_change_password(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $this->secretary->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->secretary->password));
    }

    public function test_wrong_current_password_rejected(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_password_confirmation_required(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }
}

<?php

namespace Tests\Feature\Trial;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrialExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function user(?string $trialEndsAt, bool $demo = true): User
    {
        $role = Role::firstOrCreate(['name' => $demo ? 'admin' : 'super_admin'],
            ['display_name_en' => 'A', 'display_name_ar' => 'A', 'permissions' => ['*'], 'is_system' => false]);

        return User::create([
            'name' => 'U', 'email' => 'u'.uniqid().'@t.com', 'password' => Hash::make('DemoClinic@2026'),
            'role_id' => $role->id, 'is_active' => true, 'is_demo' => $demo,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    public function test_trial_expired_page_is_public(): void
    {
        $this->get('/trial-expired')->assertOk();
    }

    public function test_active_trial_user_can_access(): void
    {
        $u = $this->user(now()->addDays(10)->toDateTimeString());
        $this->actingAs($u)->get('/admin')->assertOk();
    }

    public function test_expired_trial_user_is_blocked_and_redirected(): void
    {
        $u = $this->user(now()->subDay()->toDateTimeString());
        $this->actingAs($u)->get('/admin')->assertRedirect(route('trial.expired'));
        // and logged out
        $this->assertGuest();
    }

    public function test_non_trial_user_is_unaffected(): void
    {
        $u = $this->user(null, demo: false);
        $this->actingAs($u)->get('/admin')->assertOk();
    }

    public function test_model_helpers(): void
    {
        $this->assertTrue($this->user(now()->subDay()->toDateTimeString())->trialExpired());
        $this->assertFalse($this->user(now()->addDays(5)->toDateTimeString())->trialExpired());
        $this->assertFalse($this->user(null, demo: false)->trialExpired());
    }

    public function test_rotate_command_locks_expired_demo_accounts(): void
    {
        $expired = $this->user(now()->subDay()->toDateTimeString());
        $active = $this->user(now()->addDays(5)->toDateTimeString());
        $oldHash = $expired->password;

        $this->artisan('trials:rotate-expired')->assertExitCode(0);

        $expired->refresh();
        $active->refresh();
        $this->assertFalse($expired->is_active);          // deactivated
        $this->assertNotSame($oldHash, $expired->password); // password rotated
        $this->assertTrue($active->is_active);             // active trial untouched
    }
}

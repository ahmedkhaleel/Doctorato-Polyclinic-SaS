<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Each non-patient portal (Doctor, Secretary, Admin, Webmaster) now has
 * its own forgot/reset flow via the shared HandlesPasswordReset trait.
 * Make sure the wiring works the same in all four — and in particular
 * that cross-portal token reuse is blocked (a doctor's portal shouldn't
 * accept an admin's token).
 */
class StaffPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: array<string>}> */
    public static function portalProvider(): array
    {
        return [
            'doctor'    => ['/doctor',    ['doctor']],
            'secretary' => ['/secretary', ['secretary']],
            'admin'     => ['/admin',     ['admin']],
            'webmaster' => ['/webmaster', ['webmaster']],
        ];
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name_en' => $roleName, 'display_name_ar' => $roleName, 'permissions' => [], 'is_system' => true]
        );

        return User::create([
            'name' => "Test {$roleName}",
            'email' => "{$roleName}-pw-" . random_int(1000, 9999) . '@test.com',
            'password' => bcrypt('OldPass1234'),
            'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    /**
     * @dataProvider portalProvider
     */
    public function test_forgot_password_form_renders(string $prefix, array $roles): void
    {
        $this->get("{$prefix}/forgot-password")->assertOk();
    }

    /**
     * @dataProvider portalProvider
     */
    public function test_post_forgot_sends_link_for_matching_role(string $prefix, array $roles): void
    {
        Notification::fake();
        $user = $this->makeUser($roles[0]);

        $this->post("{$prefix}/forgot-password", ['email' => $user->email])
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * @dataProvider portalProvider
     */
    public function test_post_forgot_does_not_email_unrelated_role(string $prefix, array $roles): void
    {
        Notification::fake();
        // Make a patient user; the staff portal must not email them
        $patient = $this->makeUser('patient');

        $this->post("{$prefix}/forgot-password", ['email' => $patient->email])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    /**
     * @dataProvider portalProvider
     */
    public function test_response_does_not_leak_account_existence(string $prefix, array $roles): void
    {
        Notification::fake();
        $user = $this->makeUser($roles[0]);

        $known   = $this->post("{$prefix}/forgot-password", ['email' => $user->email]);
        $unknown = $this->post("{$prefix}/forgot-password", ['email' => 'never-' . random_int(1, 1e6) . '@nowhere.test']);

        $this->assertSame($known->status(), $unknown->status());
        $this->assertSame(
            $known->getSession()->get('success'),
            $unknown->getSession()->get('success'),
            "Forgot-password response must be identical for known and unknown emails on {$prefix}"
        );
    }

    public function test_admin_token_cannot_be_used_to_reset_a_doctor(): void
    {
        $admin = $this->makeUser('admin');
        $token = Password::createToken($admin);

        // Try to consume the admin token via the doctor portal's reset endpoint.
        // The trait's role-strict reset must refuse with 403.
        $this->post('/doctor/reset-password', [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'NewSecurePass99',
            'password_confirmation' => 'NewSecurePass99',
        ])->assertForbidden();

        $this->assertTrue(\Hash::check('OldPass1234', $admin->fresh()->password));
    }
}

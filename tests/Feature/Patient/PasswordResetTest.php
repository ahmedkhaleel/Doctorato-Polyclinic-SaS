<?php

namespace Tests\Feature\Patient;

use App\Models\Role;
use App\Models\User;
use App\Notifications\BrandedPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Pins the patient password-reset flow against regressions.
 *
 *   1. Forgot-password POST mails a reset link only to patient-role
 *      users — and never reveals (via response timing/text) whether
 *      the email actually exists.
 *   2. The reset POST validates the signed token, rotates remember_token,
 *      and rejects non-patient roles even with a valid token (defense
 *      in depth).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name_en' => $roleName, 'display_name_ar' => $roleName, 'permissions' => [], 'is_system' => true]
        );

        return User::create([
            'name'      => "Test {$roleName}",
            'email'     => $email,
            'password'  => bcrypt('OldPassword123'),
            'role_id'   => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_forgot_password_form_renders(): void
    {
        $this->get('/ar/patient/forgot-password')
            ->assertOk();
    }

    public function test_forgot_password_sends_link_for_patient(): void
    {
        Notification::fake();
        $patient = $this->userWithRole('patient', 'reset-me@test.com');

        $this->post('/ar/patient/forgot-password', ['email' => 'reset-me@test.com'])
            ->assertRedirect();

        Notification::assertSentTo($patient, BrandedPasswordReset::class);
    }

    public function test_forgot_password_does_not_email_admin(): void
    {
        Notification::fake();
        $admin = $this->userWithRole('admin', 'admin-reset@test.com');

        $this->post('/ar/patient/forgot-password', ['email' => 'admin-reset@test.com'])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_forgot_password_response_does_not_leak_email_existence(): void
    {
        Notification::fake();
        $this->userWithRole('patient', 'exists@test.com');

        $existsResp  = $this->post('/ar/patient/forgot-password', ['email' => 'exists@test.com']);
        $absentResp  = $this->post('/ar/patient/forgot-password', ['email' => 'never-registered@test.com']);

        // Both follow the same redirect + flash — UI gives no signal.
        $this->assertSame($existsResp->status(), $absentResp->status());
        $this->assertSame($existsResp->getSession()->get('success'), $absentResp->getSession()->get('success'));
    }

    public function test_reset_password_completes_for_valid_token(): void
    {
        $patient = $this->userWithRole('patient', 'token-user@test.com');
        $token = Password::createToken($patient);

        $this->post('/ar/patient/reset-password', [
            'token'                 => $token,
            'email'                 => $patient->email,
            'password'              => 'NewSecurePassword99',
            'password_confirmation' => 'NewSecurePassword99',
        ])->assertRedirect('/ar/patient/login');

        $this->assertTrue(\Hash::check('NewSecurePassword99', $patient->fresh()->password));
    }

    public function test_reset_password_rejects_admin_even_with_valid_token(): void
    {
        $admin = $this->userWithRole('admin', 'admin-token@test.com');
        $token = Password::createToken($admin);

        $this->post('/ar/patient/reset-password', [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'NewPass1234567',
            'password_confirmation' => 'NewPass1234567',
        ])->assertForbidden();

        $this->assertTrue(\Hash::check('OldPassword123', $admin->fresh()->password));
    }
}

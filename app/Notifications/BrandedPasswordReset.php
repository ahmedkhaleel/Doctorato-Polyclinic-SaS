<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Replaces Laravel's default ResetPassword notification with the branded
 * Doctorato HTML template (resources/views/emails/password-reset.blade.php).
 *
 * Picks the right portal forgot-password URL based on the user's role,
 * so a doctor's reset link goes to /doctor/reset-password/... and an
 * admin's goes to /admin/reset-password/..., not the patient one.
 */
class BrandedPasswordReset extends Notification
{
    use Queueable;

    public string $token;
    public ?string $userLocale;

    public function __construct(string $token, ?string $locale = null)
    {
        $this->token = $token;
        $this->userLocale = $locale;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $portalKey = $this->portalKeyFor($notifiable);
        $resetUrl  = $this->resetUrlFor($notifiable, $portalKey);
        $locale    = $this->userLocale ?? app()->getLocale();
        $expiry    = config('auth.passwords.users.expire', 60);

        $subject = $locale === 'ar'
            ? '[Doctorato] إعادة تعيين كلمة المرور'
            : '[Doctorato] Reset your password';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.password-reset', [
                'userName'       => $notifiable->name ?? 'there',
                'portalKey'      => $portalKey,
                'resetUrl'       => $resetUrl,
                'locale'         => $locale,
                'expiryMinutes'  => $expiry,
            ]);
    }

    /**
     * Map the user's role to the portal key shown in the email.
     */
    private function portalKeyFor($user): string
    {
        $role = $user->role?->name;
        return match ($role) {
            'admin', 'super_admin' => 'admin',
            'doctor'               => 'doctor',
            'secretary'            => 'secretary',
            'webmaster'            => 'webmaster',
            default                => 'patient',
        };
    }

    /**
     * Build the reset URL pointing at the right portal's reset form.
     * Uses each portal's named route so the link survives URL changes.
     */
    private function resetUrlFor($user, string $portalKey): string
    {
        $email = $user->getEmailForPasswordReset();
        $locale = $this->userLocale ?? app()->getLocale();

        return match ($portalKey) {
            'patient'   => url("/{$locale}/patient/reset-password/{$this->token}?email=" . urlencode($email)),
            'doctor'    => url("/doctor/reset-password/{$this->token}?email=" . urlencode($email)),
            'secretary' => url("/secretary/reset-password/{$this->token}?email=" . urlencode($email)),
            'admin'     => url("/admin/reset-password/{$this->token}?email=" . urlencode($email)),
            'webmaster' => url("/webmaster/reset-password/{$this->token}?email=" . urlencode($email)),
            default     => url("/{$locale}/patient/reset-password/{$this->token}?email=" . urlencode($email)),
        };
    }
}

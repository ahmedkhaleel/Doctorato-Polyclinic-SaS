<?php

namespace App\Notifications;

use App\Models\Patient;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to both sides of a referral redemption when the referred friend
 * makes their first booking.
 *
 *   $role: 'friend'   → "Welcome — here's your discount code"
 *          'referrer' → "Thanks for inviting your friend — here's a code"
 */
class ReferralRewardEmail extends Notification
{
    use Queueable;

    public function __construct(
        protected string $role,           // 'friend' | 'referrer'
        protected string $discountCode,
        protected float $discountAmount,
        protected ?Patient $patient = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale   = $this->patient?->preferred_language ?? 'ar';
        $isRtl    = $locale === 'ar';
        $currency = Setting::get('currency_code', 'EGP');

        $copy = $this->buildCopy($isRtl, $currency);

        $appUrl = rtrim(config('app.url'), '/');

        return (new MailMessage)
            ->subject($copy['subject'])
            ->view('emails.referral-reward', [
                'patientName'    => $this->patient?->full_name ?? ($isRtl ? 'عميلنا الكريم' : 'there'),
                'role'           => $this->role,
                'locale'         => $locale,
                'subject'        => $copy['subject'],
                'heading'        => $copy['heading'],
                'intro'          => $copy['intro'],
                'discountCode'   => $this->discountCode,
                'discountAmount' => number_format($this->discountAmount, 0) . ' ' . $currency,
                'ctaUrl'         => "{$appUrl}/{$locale}/patient/bookings/create",
                'ctaLabel'       => $isRtl ? 'احجز الآن وفعّل الكود' : 'Book now & use the code',
            ]);
    }

    private function buildCopy(bool $isRtl, string $currency): array
    {
        if ($this->role === 'friend') {
            return $isRtl
                ? [
                    'subject' => '[Doctorato] خصم خاص بك',
                    'heading' => 'مرحباً بكِ في Doctorato!',
                    'intro'   => 'تم تأكيد حجزك الأول. كهدية ترحيب من صديقك، حصلتِ على خصم خاص — استخدمي الكود التالي عند الدفع.',
                ]
                : [
                    'subject' => '[Doctorato] Your friend discount',
                    'heading' => 'Welcome to Doctorato!',
                    'intro'   => 'Your first booking is confirmed. As a welcome gift from your friend, you have a special discount — use the code below at checkout.',
                ];
        }

        // referrer
        return $isRtl
            ? [
                'subject' => '[Doctorato] شكراً للإحالة!',
                'heading' => 'شكراً لمشاركتك Doctorato',
                'intro'   => 'صديقك أكمل أول حجز معنا. كتقدير منا، إليكِ كود خصم لاستخدامه في زيارتك القادمة.',
            ]
            : [
                'subject' => '[Doctorato] Thanks for referring!',
                'heading' => 'Thanks for sharing Doctorato',
                'intro'   => 'Your friend just made their first booking with us. As a thank-you, here\'s a discount code for your next visit.',
            ];
    }
}

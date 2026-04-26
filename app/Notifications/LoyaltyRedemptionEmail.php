<?php

namespace App\Notifications;

use App\Models\Patient;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation email sent to a patient after they redeem loyalty points
 * for a discount code. Saves them from losing the code if they close
 * the page (a real UX gap before this).
 */
class LoyaltyRedemptionEmail extends Notification
{
    use Queueable;

    public function __construct(
        protected Patient $patient,
        protected string $code,
        protected float $amount,
        protected int $pointsRedeemed,
        protected string $expiresAt, // ISO date
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale   = $this->patient->preferred_language ?? 'ar';
        $isRtl    = $locale === 'ar';
        $currency = Setting::get('currency_code', 'EGP');

        $subject = $isRtl
            ? '[Doctorato] تم استبدال نقاطك — كود الخصم'
            : '[Doctorato] Loyalty redemption — your code';
        $heading = $isRtl ? 'تم تأكيد الاستبدال' : 'Redemption confirmed';
        $intro = $isRtl
            ? "استبدلت {$this->pointsRedeemed} نقطة من رصيد الولاء. استخدم الكود أدناه عند حجزك التالي. صالح حتى {$this->expiresAt}."
            : "You redeemed {$this->pointsRedeemed} points from your loyalty balance. Use the code below on your next booking. Valid until {$this->expiresAt}.";

        $appUrl = rtrim(config('app.url'), '/');

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.referral-reward', [
                'patientName'    => $this->patient->full_name ?: ($isRtl ? 'عميلنا الكريم' : 'there'),
                'role'           => 'referrer', // gold accent — matches loyalty branding
                'locale'         => $locale,
                'subject'        => $subject,
                'heading'        => $heading,
                'intro'          => $intro,
                'discountCode'   => $this->code,
                'discountAmount' => number_format($this->amount, 0) . ' ' . $currency,
                'ctaUrl'         => "{$appUrl}/{$locale}/patient/bookings/create",
                'ctaLabel'       => $isRtl ? 'احجز الآن واستخدم الكود' : 'Book now & use the code',
            ]);
    }
}

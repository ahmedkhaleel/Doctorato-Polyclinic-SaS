<?php

namespace App\Notifications;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Single email notification covering all booking-status transitions
 * sent to a patient. Picks heading + accent + body copy based on
 * $status. Bilingual (uses notifiable->preferred_language ?? 'ar').
 *
 * Statuses:
 *   - 'created'   — fired on booking creation, sets expectation
 *   - 'confirmed' — secretary/admin confirmed the appointment
 *   - 'cancelled' — booking cancelled (with optional reason)
 */
class BookingStatusEmail extends Notification
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
        protected string $status,        // 'created' | 'confirmed' | 'cancelled'
        protected ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_language ?? app()->getLocale();
        $isRtl  = $locale === 'ar';

        // Resolve booking details — handles both website bookings (single
        // service_id) and secretary bookings (bookingServices pivot).
        $serviceName = $this->booking->service?->{$isRtl ? 'name_ar' : 'name_en'}
            ?? $this->booking->bookingServices?->first()?->service?->{$isRtl ? 'name_ar' : 'name_en'}
            ?? ($isRtl ? 'استشارة عامة' : 'General consultation');

        $doctorName = $this->booking->doctor?->{$isRtl ? 'name_ar' : 'name_en'}
            ?? $this->booking->bookingServices?->first()?->doctor?->{$isRtl ? 'name_ar' : 'name_en'};

        $appointmentDate = $this->booking->preferred_date
            ?? $this->booking->appointments?->first()?->appointment_date;
        $appointmentTime = $this->booking->preferred_time
            ?? $this->booking->appointments?->first()?->start_time;

        $copy = $this->buildCopy($isRtl, $serviceName, $doctorName);

        $appUrl = rtrim(config('app.url'), '/');
        $ctaUrl = $notifiable->id
            ? "{$appUrl}/{$locale}/patient/bookings"
            : null;

        // One-click signed action links — no login required. Valid for
        // 30 days. Only useful for bookings the patient hasn't already
        // closed the loop on (confirmed/cancelled both ok for unconfirmed).
        $oneClickConfirm = null;
        $oneClickCancel  = null;
        if (in_array($this->status, ['unconfirmed', 'new', 'contacted'])) {
            try {
                $oneClickConfirm = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'booking.confirm-link', now()->addDays(30),
                    ['locale' => $locale, 'booking' => $this->booking->id]
                );
                $oneClickCancel = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'booking.cancel-link', now()->addDays(30),
                    ['locale' => $locale, 'booking' => $this->booking->id]
                );
            } catch (\Throwable $e) {
                // Route may not be loaded yet in some contexts — fail silently.
            }
        }

        return (new MailMessage)
            ->subject($copy['subject'])
            ->view('emails.booking-status', [
                'patientName'      => $this->booking->full_name
                    ?? $this->booking->patient?->full_name
                    ?? ($isRtl ? 'عميلنا الكريم' : 'there'),
                'locale'           => $locale,
                'status'           => $this->status,
                'heading'          => $copy['heading'],
                'intro'            => $copy['intro'],
                'preparationNotes' => $copy['preparationNotes'] ?? null,
                'reason'           => $this->reason,
                'bookingNumber'    => $this->booking->booking_number,
                'doctorName'       => $doctorName,
                'serviceName'      => $serviceName,
                'appointmentDate'  => $appointmentDate
                    ? Carbon::parse($appointmentDate)->translatedFormat($isRtl ? 'l، j F Y' : 'l, F j, Y')
                    : null,
                'appointmentTime'  => $appointmentTime
                    ? (is_string($appointmentTime) ? substr($appointmentTime, 0, 5) : $appointmentTime->format('H:i'))
                    : null,
                'ctaUrl'           => $ctaUrl,
                'ctaLabel'         => $isRtl ? 'عرض حجوزاتي' : 'View My Bookings',
                'oneClickConfirm'  => $oneClickConfirm,
                'oneClickCancel'   => $oneClickCancel,
            ]);
    }

    private function buildCopy(bool $isRtl, ?string $serviceName, ?string $doctorName): array
    {
        return match ($this->status) {
            'confirmed' => $isRtl
                ? [
                    'subject' => '[Doctorato] تم تأكيد حجزك',
                    'heading' => 'حجزك مؤكد',
                    'intro'   => 'تم تأكيد حجزك بنجاح. نتطلع لاستقبالك في الموعد المحدد.',
                    'preparationNotes' => 'يرجى الحضور قبل الموعد بـ 10 دقائق وإحضار بطاقة الهوية. لو في تأمين صحي، احضري بطاقة التأمين معكِ.',
                ]
                : [
                    'subject' => '[Doctorato] Booking confirmed',
                    'heading' => 'Your booking is confirmed',
                    'intro'   => 'We confirmed your booking. Looking forward to seeing you at the scheduled time.',
                    'preparationNotes' => 'Please arrive 10 minutes early and bring your ID. If you have insurance, bring the card too.',
                ],

            'cancelled' => $isRtl
                ? [
                    'subject' => '[Doctorato] تم إلغاء حجزك',
                    'heading' => 'تم إلغاء الحجز',
                    'intro'   => 'تم إلغاء حجزك. يمكنك حجز موعد جديد في أي وقت من بوابة المريض أو الموقع.',
                ]
                : [
                    'subject' => '[Doctorato] Booking cancelled',
                    'heading' => 'Booking cancelled',
                    'intro'   => 'Your booking has been cancelled. You can book a new appointment anytime from the patient portal or website.',
                ],

            default => $isRtl // 'created'
                ? [
                    'subject' => '[Doctorato] استلمنا طلب حجزك',
                    'heading' => 'تم استلام طلب الحجز',
                    'intro'   => 'شكراً لاختيارك Doctorato. استلمنا طلب الحجز وسيتواصل معكِ فريقنا قريباً للتأكيد.',
                ]
                : [
                    'subject' => '[Doctorato] Booking request received',
                    'heading' => 'Booking request received',
                    'intro'   => 'Thanks for choosing Doctorato. We received your booking request — our team will contact you shortly to confirm.',
                ],
        };
    }
}

<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the patient when staff change a booking's scheduling
 * details (date, time, doctor, service) or restore it from a
 * cancelled state. Parallel to VisitRescheduledEmail but scoped to
 * bookings (which represent appointments not yet checked in).
 */
class BookingRescheduledEmail extends Notification
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
        protected array $changes,
        protected ?string $newDoctorName = null,
        protected ?string $newServiceName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking  = $this->booking;
        $patient  = $booking->patient;

        $locale = $patient?->preferred_language ?? 'ar';
        $isRtl  = $locale === 'ar';

        // Two distinct framings depending on whether this is a
        // "we restored your cancelled booking" or "we shifted your
        // booking" message.
        $isRestore = isset($this->changes['status'])
            && ($this->changes['status']['from'] ?? null) === 'cancelled';

        $subject = $isRtl
            ? '[Doctorato] تم تحديث حجزك'
            : '[Doctorato] Your booking has been updated';

        $heading = $isRestore
            ? ($isRtl ? 'تم استعادة حجزك' : 'Your booking is back')
            : ($isRtl ? 'حجزك تم تحديثه'   : 'Your booking was updated');

        $intro = $isRestore
            ? ($isRtl
                ? 'تم إعادة تفعيل حجزك السابق. يرجى مراجعة التفاصيل أدناه — سيقوم فريق الاستقبال بإعادة تأكيدها قريباً.'
                : 'Your previously-cancelled booking has been reactivated. Please review the details below — our front desk will reconfirm shortly.')
            : ($isRtl
                ? 'قام فريق العيادة بتحديث تفاصيل حجزك. يرجى مراجعة المعلومات الجديدة.'
                : 'Our team has updated your booking. Please review the new details below.');

        $appUrl    = rtrim(config('app.url'), '/');
        $bookingUrl = "{$appUrl}/{$locale}/patient/bookings";

        // Pretty-print the changed fields for the email body.
        $rows = [];
        if (array_key_exists('status', $this->changes)) {
            $rows[] = [
                'label' => $isRtl ? 'الحالة' : 'Status',
                'from'  => $this->changes['status']['from'],
                'to'    => $this->changes['status']['to'],
            ];
        }
        if (array_key_exists('preferred_date', $this->changes)) {
            $rows[] = [
                'label' => $isRtl ? 'التاريخ' : 'Date',
                'from'  => $this->changes['preferred_date']['from'],
                'to'    => $this->changes['preferred_date']['to'],
            ];
        }
        if (array_key_exists('preferred_time', $this->changes)) {
            $rows[] = [
                'label' => $isRtl ? 'الوقت' : 'Time',
                'from'  => $this->changes['preferred_time']['from'] ?: '—',
                'to'    => $this->changes['preferred_time']['to']   ?: '—',
            ];
        }
        if (array_key_exists('doctor_id', $this->changes)) {
            $rows[] = [
                'label' => $isRtl ? 'الطبيب' : 'Doctor',
                'from'  => $this->changes['doctor_id']['from_name'] ?? '—',
                'to'    => $this->newDoctorName ?? ($this->changes['doctor_id']['to_name'] ?? '—'),
            ];
        }
        if (array_key_exists('service_id', $this->changes)) {
            $rows[] = [
                'label' => $isRtl ? 'الخدمة' : 'Service',
                'from'  => $this->changes['service_id']['from_name'] ?? '—',
                'to'    => $this->newServiceName ?? ($this->changes['service_id']['to_name'] ?? '—'),
            ];
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.visit-rescheduled', [   // reusing the same template
                'subject'     => $subject,
                'heading'     => $heading,
                'intro'       => $intro,
                'locale'      => $locale,
                'patientName' => $patient?->full_name ?: ($isRtl ? 'عميلنا الكريم' : 'there'),
                'rows'        => $rows,
                'newDate'     => $booking->preferred_date instanceof \DateTimeInterface
                                    ? $booking->preferred_date->format('Y-m-d')
                                    : (string) $booking->preferred_date,
                'newTime'     => $booking->preferred_time,
                'visitUrl'    => $bookingUrl,
            ]);
    }
}

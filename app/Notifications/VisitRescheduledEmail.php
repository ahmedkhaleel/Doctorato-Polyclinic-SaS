<?php

namespace App\Notifications;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the patient when staff (admin / secretary) changes the
 * scheduling details of one of their visits — date, time, doctor,
 * or service. Closes the communication loop so the patient doesn't
 * show up at the wrong slot or expect the wrong provider.
 *
 * Caller passes a $changes payload describing what shifted, e.g.:
 *   ['visit_date' => ['from' => '2026-05-01', 'to' => '2026-05-03'],
 *    'doctor_id'  => ['from' => 4, 'to' => 7]]
 */
class VisitRescheduledEmail extends Notification
{
    use Queueable;

    public function __construct(
        protected Visit $visit,
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
        $visit  = $this->visit;
        $patient = $visit->patient;

        $locale = $patient?->preferred_language ?? 'ar';
        $isRtl  = $locale === 'ar';

        $subject = $isRtl
            ? '[Doctorato] تم تحديث موعد زيارتك'
            : '[Doctorato] Your appointment has been updated';

        $heading = $isRtl ? 'موعدك تم تحديثه' : 'Your appointment was updated';
        $intro   = $isRtl
            ? 'قام فريق العيادة بتحديث تفاصيل زيارتك. يرجى مراجعة المعلومات الجديدة أدناه.'
            : 'Our team has updated the details of your appointment. Please review the new information below.';

        $appUrl = rtrim(config('app.url'), '/');
        $portalUrl = "{$appUrl}/{$locale}/patient/visits/{$visit->id}";

        // Pretty-print the changed fields for the email body.
        $rows = [];
        if (array_key_exists('visit_date', $this->changes)) {
            $rows[] = [
                'label' => $isRtl ? 'التاريخ' : 'Date',
                'from'  => $this->changes['visit_date']['from'],
                'to'    => $this->changes['visit_date']['to'],
            ];
        }
        if (array_key_exists('scheduled_time', $this->changes)) {
            $rows[] = [
                'label' => $isRtl ? 'الوقت' : 'Time',
                'from'  => $this->changes['scheduled_time']['from'] ?: '—',
                'to'    => $this->changes['scheduled_time']['to']   ?: '—',
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
            ->view('emails.visit-rescheduled', [
                'subject'     => $subject,
                'heading'     => $heading,
                'intro'       => $intro,
                'locale'      => $locale,
                'patientName' => $patient?->full_name ?: ($isRtl ? 'عميلنا الكريم' : 'there'),
                'rows'        => $rows,
                'newDate'     => $visit->visit_date instanceof \DateTimeInterface
                                    ? $visit->visit_date->format('Y-m-d')
                                    : (string) $visit->visit_date,
                'newTime'     => $visit->scheduled_time,
                'visitUrl'    => $portalUrl,
            ]);
    }
}

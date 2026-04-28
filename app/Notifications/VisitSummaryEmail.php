<?php

namespace App\Notifications;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Visit summary email — sent after a visit is marked completed. Gives
 * the patient a tidy record of the encounter (doctor, date, diagnosis,
 * prescription) and a deep link to the invoice. Closes a real loyalty
 * loop: many patients forget what was prescribed within hours.
 */
class VisitSummaryEmail extends Notification
{
    use Queueable;

    public function __construct(protected Visit $visit) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visit   = $this->visit;
        $patient = $visit->patient;

        $locale = $patient?->preferred_language ?? 'ar';
        $isRtl  = $locale === 'ar';

        $doctorName = $isRtl
            ? ($visit->doctor?->name_ar ?? $visit->doctor?->name_en ?? '—')
            : ($visit->doctor?->name_en ?? $visit->doctor?->name_ar ?? '—');
        $serviceName = $isRtl
            ? ($visit->service?->name_ar ?? $visit->service?->name_en)
            : ($visit->service?->name_en ?? $visit->service?->name_ar);

        // Pull prescriptions written for this visit (if any).
        $prescriptions = collect($visit->prescriptions ?? [])
            ->flatMap(fn ($rx) => collect($rx->items ?? [])->map(fn ($it) => [
                'name'     => $isRtl ? ($it->medication?->name_ar ?? $it->medication?->name_en ?? $it->name) : ($it->medication?->name_en ?? $it->medication?->name_ar ?? $it->name),
                'dosage'   => $it->dosage,
                'duration' => $it->duration,
            ]))
            ->take(15) // safety cap on huge prescriptions
            ->values()
            ->all();

        $appUrl     = rtrim(config('app.url'), '/');
        $invoiceUrl = $visit->invoice
            ? "{$appUrl}/{$locale}/patient/invoices/{$visit->invoice->id}"
            : null;
        $portalUrl  = "{$appUrl}/{$locale}/patient";

        $subject = $isRtl
            ? '[Doctorato] ملخّص زيارتك بتاريخ ' . $visit->visit_date
            : '[Doctorato] Your visit summary — ' . $visit->visit_date;

        $heading = $isRtl ? 'شكراً لزيارتك' : 'Thanks for your visit';
        $intro   = $isRtl
            ? 'فيما يلي ملخّص زيارتك. احتفظ بهذا البريد للرجوع إليه لاحقاً.'
            : 'Here is a summary of your visit. Save this email for your records.';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.visit-summary', [
                'subject'       => $subject,
                'heading'       => $heading,
                'intro'         => $intro,
                'locale'        => $locale,
                'patientName'   => $patient?->full_name ?: ($isRtl ? 'عميلنا الكريم' : 'there'),
                'visitDate'     => $visit->visit_date,
                'doctorName'    => $doctorName,
                'serviceName'   => $serviceName,
                'diagnosis'     => $visit->diagnosis,
                'prescriptions' => $prescriptions,
                'invoiceUrl'    => $invoiceUrl,
                'portalUrl'     => $portalUrl,
            ]);
    }
}

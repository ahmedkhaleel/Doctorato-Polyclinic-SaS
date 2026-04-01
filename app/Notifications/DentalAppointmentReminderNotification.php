<?php

namespace App\Notifications;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalAppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Visit $visit,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patient = $this->visit->patient;
        $riskFlags = $patient ? $patient->getDentalRiskFlags() : [];
        $highRiskFlags = array_filter($riskFlags, fn ($f) => $f['severity'] === 'high');
        $hasRisks = !empty($highRiskFlags);

        $message = "Dental appointment today: {$patient?->full_name} — {$this->visit->service?->name_en}";
        if ($hasRisks) {
            $riskLabels = implode(', ', array_map(fn ($f) => $f['label_en'], $highRiskFlags));
            $message .= " ⚠ ALERTS: {$riskLabels}";
        }

        return [
            'type' => 'dental_appointment_reminder',
            'visit_id' => $this->visit->id,
            'patient_name' => $patient?->full_name ?? 'Unknown',
            'patient_id' => $this->visit->patient_id,
            'doctor_name' => $this->visit->doctor?->name_en ?? '',
            'service_name' => $this->visit->service?->name_en ?? 'Dental Visit',
            'visit_date' => $this->visit->visit_date?->format('Y-m-d'),
            'visit_type' => $this->visit->visit_type,
            'has_medical_alerts' => $hasRisks,
            'medical_alerts' => array_map(fn ($f) => $f['label_en'], $highRiskFlags),
            'allergies' => $patient?->allergies,
            'blood_type' => $patient?->blood_type,
            'message' => $message,
            'priority' => $hasRisks ? 'high' : 'normal',
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\TreatmentPlanConsent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConsentRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected TreatmentPlanConsent $consent,
        protected string $type = 'request', // request, signed, declined
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $plan = $this->consent->treatmentPlan;
        $patient = $this->consent->patient;
        $planTitle = $plan?->title_en ?: $plan?->title_ar ?: 'Treatment Plan #' . $plan?->id;
        $patientName = $patient?->full_name ?? 'Unknown';

        $messages = [
            'request' => "Consent request sent to {$patientName} for \"{$planTitle}\"",
            'signed' => "{$patientName} signed the consent for \"{$planTitle}\"",
            'declined' => "{$patientName} declined the consent for \"{$planTitle}\"",
        ];

        $priorities = [
            'request' => 'medium',
            'signed' => 'high',
            'declined' => 'high',
        ];

        return [
            'type' => 'dental_consent_' . $this->type,
            'consent_id' => $this->consent->id,
            'plan_id' => $plan?->id,
            'patient_id' => $patient?->id,
            'patient_name' => $patientName,
            'plan_title' => $planTitle,
            'consent_status' => $this->consent->status,
            'message' => $messages[$this->type] ?? $messages['request'],
            'priority' => $priorities[$this->type] ?? 'medium',
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\DentalTreatmentPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalTreatmentPlanDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DentalTreatmentPlan $plan,
        protected string $reason = 'overdue',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patientName = $this->plan->patient?->full_name ?? 'Unknown';
        $planTitle = $this->plan->title_en ?: $this->plan->title_ar;

        $message = match ($this->reason) {
            'overdue' => "Treatment plan overdue: \"{$planTitle}\" for {$patientName}",
            'approaching' => "Treatment plan ending soon: \"{$planTitle}\" for {$patientName}",
            default => "Treatment plan update: \"{$planTitle}\" for {$patientName}",
        };

        return [
            'type' => 'dental_plan_due',
            'plan_id' => $this->plan->id,
            'patient_name' => $patientName,
            'patient_id' => $this->plan->patient_id,
            'plan_title' => $planTitle,
            'doctor_name' => $this->plan->doctor?->name_en ?? '',
            'expected_end_date' => $this->plan->expected_end_date,
            'status' => $this->plan->status,
            'reason' => $this->reason,
            'message' => $message,
            'priority' => $this->reason === 'overdue' ? 'high' : 'medium',
        ];
    }
}

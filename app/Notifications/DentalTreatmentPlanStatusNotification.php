<?php

namespace App\Notifications;

use App\Models\DentalTreatmentPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalTreatmentPlanStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DentalTreatmentPlan $plan,
        protected string $oldStatus,
        protected string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patientName = $this->plan->patient?->full_name ?? 'Unknown';
        $planTitle = $this->plan->title_en ?: $this->plan->title_ar ?: 'Treatment Plan';

        $statusLabels = [
            'draft' => 'Draft',
            'approved' => 'Approved',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $newLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return [
            'type' => 'dental_plan_status_changed',
            'plan_id' => $this->plan->id,
            'patient_id' => $this->plan->patient_id,
            'patient_name' => $patientName,
            'doctor_name' => $this->plan->doctor?->name_en ?? '',
            'plan_title' => $planTitle,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Treatment plan \"{$planTitle}\" for {$patientName}: status changed to {$newLabel}",
            'priority' => in_array($this->newStatus, ['approved', 'cancelled']) ? 'high' : 'medium',
        ];
    }
}

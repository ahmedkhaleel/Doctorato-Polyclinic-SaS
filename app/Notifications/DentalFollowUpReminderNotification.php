<?php

namespace App\Notifications;

use App\Models\DentalTreatment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalFollowUpReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DentalTreatment $treatment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patientName = $this->treatment->patient?->full_name ?? 'Unknown';
        $treatmentType = str_replace('_', ' ', $this->treatment->treatment_type);
        $completedDate = $this->treatment->completed_at?->format('Y-m-d') ?? '';
        $daysSince = $this->treatment->completed_at ? now()->diffInDays($this->treatment->completed_at) : 0;

        return [
            'type' => 'dental_followup_reminder',
            'treatment_id' => $this->treatment->id,
            'patient_name' => $patientName,
            'patient_id' => $this->treatment->patient_id,
            'doctor_name' => $this->treatment->doctor?->name_en ?? '',
            'treatment_type' => $this->treatment->treatment_type,
            'tooth_number' => $this->treatment->tooth_number,
            'completed_at' => $completedDate,
            'days_since' => $daysSince,
            'message' => "Follow-up needed: {$treatmentType} (tooth #{$this->treatment->tooth_number}) for {$patientName} — completed {$daysSince} days ago",
            'priority' => $daysSince > 14 ? 'high' : 'medium',
        ];
    }
}

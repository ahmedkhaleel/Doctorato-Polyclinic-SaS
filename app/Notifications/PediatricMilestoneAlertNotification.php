<?php

namespace App\Notifications;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PediatricMilestoneAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Patient $patient,
        protected int $delayedCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pediatric_milestone_alert',
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->full_name ?? 'Unknown',
            'delayed_count' => $this->delayedCount,
            'message' => "{$this->patient->full_name} has {$this->delayedCount} developmental milestone(s) not yet achieved",
            'priority' => $this->delayedCount >= 3 ? 'high' : 'normal',
        ];
    }
}

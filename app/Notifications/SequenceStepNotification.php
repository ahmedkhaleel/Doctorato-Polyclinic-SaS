<?php

namespace App\Notifications;

use App\Models\FollowUpSequenceStep;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SequenceStepNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Lead $lead,
        protected FollowUpSequenceStep $step,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sequence_step',
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->full_name,
            'lead_phone' => $this->lead->phone,
            'sequence_name' => $this->step->sequence->name ?? 'Automation',
            'action_type' => $this->step->action_type,
            'message' => $this->step->notification_message
                ?? "Action required for lead: {$this->lead->full_name}",
        ];
    }
}

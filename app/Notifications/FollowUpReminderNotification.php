<?php

namespace App\Notifications;

use App\Models\LeadFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowUpReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected LeadFollowUp $followUp,
        protected string $reminderType = 'upcoming', // 'upcoming' or 'overdue'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $lead = $this->followUp->lead;
        $isOverdue = $this->reminderType === 'overdue';

        return [
            'type' => $isOverdue ? 'follow_up_overdue' : 'follow_up_reminder',
            'lead_id' => $lead->id,
            'lead_name' => $lead->full_name,
            'follow_up_id' => $this->followUp->id,
            'follow_up_type' => $this->followUp->type,
            'scheduled_at' => $this->followUp->scheduled_at->format('Y-m-d H:i'),
            'message' => $isOverdue
                ? "Overdue follow-up: {$this->followUp->type} with {$lead->full_name}"
                : "Upcoming follow-up: {$this->followUp->type} with {$lead->full_name}",
        ];
    }
}

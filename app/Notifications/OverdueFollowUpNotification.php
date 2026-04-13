<?php

namespace App\Notifications;

use App\Models\LeadFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueFollowUpNotification extends Notification
{
    use Queueable;

    public function __construct(public LeadFollowUp $followUp) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'follow_up_id' => $this->followUp->id,
            'lead_id' => $this->followUp->lead_id,
            'lead_name' => $this->followUp->lead?->full_name ?? 'Unknown',
            'type_label' => $this->followUp->type,
            'scheduled_at' => $this->followUp->scheduled_at->toIso8601String(),
            'message_en' => "Overdue follow-up for {$this->followUp->lead?->full_name}",
            'message_ar' => "متابعة متأخرة لـ {$this->followUp->lead?->full_name}",
            'type' => 'overdue_follow_up',
            'url' => "/admin/leads/{$this->followUp->lead_id}",
        ];
    }
}

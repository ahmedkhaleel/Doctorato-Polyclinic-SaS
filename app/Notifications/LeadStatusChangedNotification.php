<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeadStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public string $oldStatus,
        public string $newStatus,
        public ?string $changedByName = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->full_name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedByName,
            'message_en' => "{$this->lead->full_name} moved from {$this->oldStatus} to {$this->newStatus}",
            'message_ar' => "تم نقل {$this->lead->full_name} من {$this->oldStatus} إلى {$this->newStatus}",
            'type' => 'lead_status_changed',
            'url' => "/admin/leads/{$this->lead->id}",
        ];
    }
}

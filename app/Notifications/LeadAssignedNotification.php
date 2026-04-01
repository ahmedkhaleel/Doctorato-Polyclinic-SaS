<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Lead $lead,
        protected ?string $assignedBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lead_assigned',
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->full_name,
            'lead_phone' => $this->lead->phone,
            'source' => $this->lead->source?->name_en ?? 'Unknown',
            'assigned_by' => $this->assignedBy,
            'message' => "New lead assigned: {$this->lead->full_name}",
        ];
    }
}

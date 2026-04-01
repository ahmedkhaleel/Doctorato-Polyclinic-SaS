<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewWebsiteLeadNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Lead $lead,
        protected string $source = 'website',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $sourceLabels = [
            'contact_form' => 'Contact Form',
            'website' => 'Website Booking',
            'booking' => 'Website Booking',
        ];

        $label = $sourceLabels[$this->source] ?? $this->source;

        return [
            'type' => 'new_website_lead',
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->full_name,
            'lead_phone' => $this->lead->phone,
            'lead_email' => $this->lead->email,
            'source' => $this->source,
            'source_label' => $label,
            'priority' => $this->lead->priority,
            'message' => "New lead from {$label}: {$this->lead->full_name}",
        ];
    }
}

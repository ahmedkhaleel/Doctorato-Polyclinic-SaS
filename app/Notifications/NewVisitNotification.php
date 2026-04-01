<?php

namespace App\Notifications;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewVisitNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Visit $visit,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_visit',
            'visit_id' => $this->visit->id,
            'patient_name' => $this->visit->patient?->full_name ?? 'Unknown',
            'service_name' => $this->visit->service?->name_en ?? 'Consultation',
            'visit_type' => $this->visit->visit_type,
            'visit_date' => $this->visit->visit_date?->format('Y-m-d'),
            'message' => "New {$this->visit->visit_type} visit for {$this->visit->patient?->full_name}",
        ];
    }
}

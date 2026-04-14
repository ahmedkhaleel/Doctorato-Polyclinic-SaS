<?php

namespace App\Notifications;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PediatricVaccinationDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Patient $patient,
        protected array $vaccinations,
        protected string $alertType = 'upcoming', // upcoming | overdue
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->vaccinations);
        $names = collect($this->vaccinations)->pluck('vaccine_name')->implode(', ');

        if ($this->alertType === 'overdue') {
            $message = "{$this->patient->full_name} has {$count} overdue vaccination(s): {$names}";
        } else {
            $message = "{$this->patient->full_name} has {$count} upcoming vaccination(s): {$names}";
        }

        return [
            'type' => $this->alertType === 'overdue' ? 'pediatric_vaccination_overdue' : 'pediatric_vaccination_due',
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->full_name ?? 'Unknown',
            'guardian_name' => $this->patient->guardian_name,
            'vaccination_count' => $count,
            'vaccine_names' => $names,
            'message' => $message,
            'priority' => $this->alertType === 'overdue' ? 'high' : 'normal',
        ];
    }
}

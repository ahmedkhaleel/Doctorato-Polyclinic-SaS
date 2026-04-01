<?php

namespace App\Notifications;

use App\Models\DentalTreatment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalTreatmentCompletedNotification extends Notification
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
        $toothLabel = $this->treatment->tooth_number ? " (#{$this->treatment->tooth_number})" : '';
        $typeLabel = str_replace('_', ' ', $this->treatment->treatment_type);

        return [
            'type' => 'dental_treatment_completed',
            'treatment_id' => $this->treatment->id,
            'patient_id' => $this->treatment->patient_id,
            'patient_name' => $patientName,
            'doctor_name' => $this->treatment->doctor?->name_en ?? '',
            'treatment_type' => $this->treatment->treatment_type,
            'tooth_number' => $this->treatment->tooth_number,
            'cost' => (float) ($this->treatment->cost + $this->treatment->lab_cost),
            'has_invoice' => (bool) $this->treatment->invoice_id,
            'message' => "Dental treatment completed: {$typeLabel}{$toothLabel} for {$patientName}",
            'priority' => 'medium',
        ];
    }
}

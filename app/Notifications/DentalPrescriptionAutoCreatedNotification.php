<?php

namespace App\Notifications;

use App\Models\DentalTreatment;
use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalPrescriptionAutoCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Prescription $prescription,
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
        $itemCount = $this->prescription->items()->count();

        return [
            'type' => 'dental_prescription_auto_created',
            'prescription_id' => $this->prescription->id,
            'treatment_id' => $this->treatment->id,
            'patient_id' => $this->treatment->patient_id,
            'patient_name' => $patientName,
            'treatment_type' => $this->treatment->treatment_type,
            'tooth_number' => $this->treatment->tooth_number,
            'medications_count' => $itemCount,
            'message' => "Auto-prescription created ({$itemCount} medications) after {$typeLabel}{$toothLabel} for {$patientName} — please review",
            'priority' => 'medium',
        ];
    }
}

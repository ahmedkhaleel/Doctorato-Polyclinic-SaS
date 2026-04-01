<?php

namespace App\Notifications;

use App\Models\DentalLabOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalLabOrderReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DentalLabOrder $labOrder,
        protected string $newStatus = 'ready',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patientName = $this->labOrder->patient?->full_name ?? 'Unknown';
        $itemType = str_replace('_', ' ', $this->labOrder->item_type);

        $messageMap = [
            'ready' => "Lab order ready for pickup: {$itemType} for {$patientName}",
            'delivered' => "Lab order delivered: {$itemType} for {$patientName}",
            'adjustment' => "Lab order needs adjustment: {$itemType} for {$patientName}",
        ];

        return [
            'type' => 'dental_lab_order_status',
            'lab_order_id' => $this->labOrder->id,
            'patient_id' => $this->labOrder->patient_id,
            'patient_name' => $patientName,
            'doctor_name' => $this->labOrder->doctor?->name_en ?? '',
            'lab_name' => $this->labOrder->lab_name ?? '',
            'item_type' => $this->labOrder->item_type,
            'tooth_number' => $this->labOrder->tooth_number,
            'new_status' => $this->newStatus,
            'message' => $messageMap[$this->newStatus] ?? "Lab order status changed to {$this->newStatus}: {$itemType} for {$patientName}",
            'priority' => $this->newStatus === 'ready' ? 'high' : 'medium',
        ];
    }
}

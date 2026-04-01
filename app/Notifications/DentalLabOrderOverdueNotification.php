<?php

namespace App\Notifications;

use App\Models\DentalLabOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DentalLabOrderOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DentalLabOrder $labOrder,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $daysOverdue = now()->diffInDays($this->labOrder->expected_date);

        return [
            'type' => 'dental_lab_overdue',
            'lab_order_id' => $this->labOrder->id,
            'patient_name' => $this->labOrder->patient?->full_name ?? 'Unknown',
            'patient_id' => $this->labOrder->patient_id,
            'lab_name' => $this->labOrder->lab_name ?? 'Unknown Lab',
            'item_type' => $this->labOrder->item_type,
            'tooth_number' => $this->labOrder->tooth_number,
            'expected_date' => $this->labOrder->expected_date,
            'days_overdue' => $daysOverdue,
            'order_number' => $this->labOrder->order_number,
            'message' => "Overdue lab order: {$this->labOrder->item_type} for {$this->labOrder->patient?->full_name} ({$daysOverdue} days overdue)",
            'priority' => $daysOverdue > 7 ? 'high' : 'medium',
        ];
    }
}

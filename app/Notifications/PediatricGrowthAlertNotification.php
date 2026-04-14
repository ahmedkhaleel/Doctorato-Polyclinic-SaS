<?php

namespace App\Notifications;

use App\Models\Patient;
use App\Models\PediatricGrowthRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PediatricGrowthAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Patient $patient,
        protected PediatricGrowthRecord $record,
        protected array $alerts, // e.g. ['underweight', 'stunted']
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $alertLabels = collect($this->alerts)->map(fn ($a) => match ($a) {
            'underweight' => "underweight (P{$this->record->weight_percentile})",
            'overweight' => "overweight (P{$this->record->weight_percentile})",
            'stunted' => "stunted (P{$this->record->height_percentile})",
            'microcephaly' => "microcephaly (P{$this->record->head_percentile})",
            default => $a,
        })->implode(', ');

        return [
            'type' => 'pediatric_growth_alert',
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->full_name ?? 'Unknown',
            'record_id' => $this->record->id,
            'alerts' => $this->alerts,
            'weight_percentile' => $this->record->weight_percentile,
            'height_percentile' => $this->record->height_percentile,
            'head_percentile' => $this->record->head_percentile,
            'message' => "Growth alert for {$this->patient->full_name}: {$alertLabels}",
            'priority' => 'high',
        ];
    }
}

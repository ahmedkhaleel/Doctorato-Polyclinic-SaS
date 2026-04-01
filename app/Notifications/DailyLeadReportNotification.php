<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DailyLeadReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected array $reportData,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'daily_lead_report',
            'date' => $this->reportData['date'],
            'new_leads_count' => $this->reportData['new_leads_count'],
            'overdue_follow_ups_count' => $this->reportData['overdue_follow_ups_count'],
            'completed_follow_ups_count' => $this->reportData['completed_follow_ups_count'],
            'hot_leads_count' => $this->reportData['hot_leads_count'],
            'converted_today' => $this->reportData['converted_today'],
            'pending_bookings' => $this->reportData['pending_bookings'],
            'message' => $this->buildMessage(),
        ];
    }

    protected function buildMessage(): string
    {
        $d = $this->reportData;
        $parts = [];

        if ($d['new_leads_count'] > 0) {
            $parts[] = "{$d['new_leads_count']} new lead(s)";
        }
        if ($d['overdue_follow_ups_count'] > 0) {
            $parts[] = "{$d['overdue_follow_ups_count']} overdue follow-up(s)";
        }
        if ($d['converted_today'] > 0) {
            $parts[] = "{$d['converted_today']} converted";
        }

        if (empty($parts)) {
            return "Daily Report: No new activity today.";
        }

        return "Daily Report: " . implode(', ', $parts);
    }
}

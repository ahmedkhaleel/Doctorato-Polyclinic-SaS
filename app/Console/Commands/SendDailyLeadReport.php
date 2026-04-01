<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use App\Notifications\DailyLeadReportNotification;
use Illuminate\Console\Command;

class SendDailyLeadReport extends Command
{
    protected $signature = 'leads:daily-report';

    protected $description = 'Send daily lead report to admin users with new leads, overdue follow-ups, and key metrics';

    public function handle(): int
    {
        $today = now()->startOfDay();

        $reportData = [
            'date' => now()->format('Y-m-d'),
            'new_leads_count' => Lead::whereDate('created_at', $today)->count(),
            'overdue_follow_ups_count' => LeadFollowUp::where('status', 'pending')
                ->where('scheduled_at', '<', now())
                ->count(),
            'completed_follow_ups_count' => LeadFollowUp::where('status', 'completed')
                ->whereDate('completed_at', $today)
                ->count(),
            'hot_leads_count' => Lead::where('priority', 1)
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])
                ->count(),
            'converted_today' => Lead::where('status', Lead::STATUS_CONVERTED)
                ->whereDate('updated_at', $today)
                ->count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
        ];

        // Send to all admin users
        $admins = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))
            ->where('is_active', true)
            ->get();

        $notification = new DailyLeadReportNotification($reportData);

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        $this->info("Daily report sent to {$admins->count()} admin(s).");
        $this->info("New leads: {$reportData['new_leads_count']}");
        $this->info("Overdue follow-ups: {$reportData['overdue_follow_ups_count']}");
        $this->info("Hot leads: {$reportData['hot_leads_count']}");

        return Command::SUCCESS;
    }
}

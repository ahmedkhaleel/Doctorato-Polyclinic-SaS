<?php

namespace App\Console\Commands;

use App\Models\LeadFollowUp;
use App\Models\User;
use App\Notifications\FollowUpReminderNotification;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'leads:remind-follow-ups';
    protected $description = 'Send notifications for upcoming and overdue follow-ups';

    public function handle(): int
    {
        $upcomingCount = 0;
        $overdueCount = 0;

        // Upcoming follow-ups in the next 2 hours that haven't been reminded
        $upcoming = LeadFollowUp::with(['lead', 'assignedUser'])
            ->pending()
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addHours(2))
            ->where('reminder_sent', 0)
            ->get();

        foreach ($upcoming as $followUp) {
            $user = $followUp->assignedUser;
            if ($user) {
                $user->notify(new FollowUpReminderNotification($followUp, 'upcoming'));
                $followUp->update(['reminder_sent' => 1]);
                $upcomingCount++;
            }
        }

        // Overdue follow-ups that haven't been reminded about overdue status
        $overdue = LeadFollowUp::with(['lead', 'assignedUser'])
            ->overdue()
            ->where('reminder_sent', '<', 2)
            ->get();

        foreach ($overdue as $followUp) {
            $user = $followUp->assignedUser;
            if ($user) {
                $user->notify(new FollowUpReminderNotification($followUp, 'overdue'));
                $followUp->update(['reminder_sent' => 2]);
                $overdueCount++;
            }
        }

        $this->info("Sent {$upcomingCount} upcoming reminders and {$overdueCount} overdue reminders.");

        return Command::SUCCESS;
    }
}

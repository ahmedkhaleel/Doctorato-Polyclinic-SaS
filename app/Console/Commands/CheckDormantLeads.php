<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Console\Command;

class CheckDormantLeads extends Command
{
    protected $signature = 'leads:check-dormant {--days=30 : Days of inactivity before marking as dormant}';
    protected $description = 'Mark leads as dormant if they have not been contacted for a specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $leads = Lead::inPipeline()
            ->where(function ($q) use ($days) {
                $q->where('last_contacted_at', '<', now()->subDays($days))
                  ->orWhere(function ($q2) use ($days) {
                      $q2->whereNull('last_contacted_at')
                         ->where('created_at', '<', now()->subDays($days));
                  });
            })
            ->get();

        $count = 0;
        foreach ($leads as $lead) {
            $oldStatus = $lead->status;
            $lead->update(['status' => Lead::STATUS_DORMANT]);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'type' => 'system',
                'subject' => "Auto-marked as dormant (no contact for {$days}+ days)",
            ]);

            LeadActivity::logStatusChange($lead, $oldStatus, Lead::STATUS_DORMANT);
            $count++;
        }

        $this->info("{$count} leads marked as dormant.");

        return Command::SUCCESS;
    }
}

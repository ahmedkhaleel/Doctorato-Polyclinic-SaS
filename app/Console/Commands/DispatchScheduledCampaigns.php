<?php

namespace App\Console\Commands;

use App\Models\NotificationCampaign;
use App\Services\Notifications\CampaignService;
use Illuminate\Console\Command;

/**
 * Sends campaigns whose scheduled_at has arrived. Scheduled in console.php.
 */
class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'notifications:dispatch-campaigns';

    protected $description = 'Send scheduled notification campaigns that are now due';

    public function handle(CampaignService $campaigns): int
    {
        $due = NotificationCampaign::where('status', NotificationCampaign::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $campaign) {
            $n = $campaigns->send($campaign);
            $this->info("Campaign #{$campaign->id} '{$campaign->name}' → {$n} recipients.");
        }

        $this->info("{$due->count()} scheduled campaign(s) dispatched.");

        return self::SUCCESS;
    }
}

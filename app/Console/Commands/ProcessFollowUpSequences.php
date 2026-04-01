<?php

namespace App\Console\Commands;

use App\Services\FollowUpAutomationService;
use Illuminate\Console\Command;

class ProcessFollowUpSequences extends Command
{
    protected $signature = 'sequences:process';
    protected $description = 'Process due follow-up automation sequence steps';

    public function handle(): int
    {
        $result = FollowUpAutomationService::processDueSteps();

        $this->info("Processed: {$result['processed']}, Failed: {$result['failed']}");

        return Command::SUCCESS;
    }
}

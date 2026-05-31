<?php

namespace App\Console\Commands;

use App\Services\Notifications\SequenceService;
use Illuminate\Console\Command;

/** Advances due drip-sequence enrolments (sends the next step). Scheduled. */
class RunNotificationSequences extends Command
{
    protected $signature = 'notifications:run-sequences {--limit=500}';

    protected $description = 'Advance due drip-sequence enrolments';

    public function handle(SequenceService $sequences): int
    {
        $n = $sequences->advanceDue((int) $this->option('limit'));
        $this->info("{$n} sequence enrolment(s) advanced.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\InsurancePreAuthorization;
use Illuminate\Console\Command;

/**
 * Auto-expire approved pre-authorizations whose validity window has passed,
 * so a stale approval can't be used to back a claim. Runs daily.
 *
 * Usage: php artisan insurance:expire-pre-auths
 */
class InsuranceExpirePreAuthsCommand extends Command
{
    protected $signature   = 'insurance:expire-pre-auths';
    protected $description = 'Mark approved insurance pre-authorizations as expired once their valid_until date passes.';

    public function handle(): int
    {
        $expired = InsurancePreAuthorization::whereIn('status', ['approved', 'partially_approved'])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} pre-authorization(s).");

        return Command::SUCCESS;
    }
}

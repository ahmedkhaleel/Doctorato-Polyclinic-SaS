<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * After a trial ends, scramble the account password and deactivate it so the
 * shared demo credentials stop working ("password auto-changes after N days").
 * The EnforceTrialExpiry middleware already blocks expired accounts live; this
 * is the persistent lock-out. Runs once per account (deactivated accounts are
 * skipped on subsequent runs).
 */
class RotateExpiredTrials extends Command
{
    protected $signature = 'trials:rotate-expired';

    protected $description = 'Scramble password + deactivate demo/trial accounts whose trial has ended.';

    public function handle(): int
    {
        $expired = User::where('is_demo', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->where('is_active', true)
            ->get();

        foreach ($expired as $user) {
            $user->forceFill([
                'password' => Hash::make(Str::random(48)),
                'is_active' => false,
            ])->save();
        }

        $n = $expired->count();
        if ($n > 0) {
            Log::info("[trials:rotate-expired] locked {$n} expired trial account(s).");
        }
        $this->info("Locked {$n} expired trial account(s).");

        return self::SUCCESS;
    }
}

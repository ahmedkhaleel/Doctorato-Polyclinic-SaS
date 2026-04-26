<?php

namespace App\Console\Commands;

use App\Models\LoyaltyPoint;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Materialize loyalty point expiry as audit-trail rows.
 *
 * Earn rows already drop out of `balance()` automatically when their
 * `expires_at` passes (the filter excludes them), so this command does
 * NOT change anyone's balance. Its purpose is purely book-keeping:
 *
 *   1. Find earn rows whose expires_at has passed.
 *   2. Skip ones we already wrote a tombstone for.
 *   3. Insert an `expire` row referencing the original earn row, with
 *      `expires_at` mirrored from the source so the tombstone itself
 *      is also excluded from balance (no double counting).
 *
 * Result: patient's transaction history shows a clear "X points
 * expired on Y" entry. Idempotent — safe to run hourly if desired.
 */
class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire-points {--dry-run : Show what would be expired without changing anything}';

    protected $description = 'Write tombstone rows for loyalty earn points whose expires_at has passed';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find earn rows past expiry that don't already have an expire tombstone
        $alreadyExpired = LoyaltyPoint::where('type', LoyaltyPoint::TYPE_EXPIRE)
            ->where('reference_type', (new LoyaltyPoint)->getMorphClass())
            ->pluck('reference_id');

        $expiredEarns = LoyaltyPoint::where('type', LoyaltyPoint::TYPE_EARN)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNotIn('id', $alreadyExpired)
            ->orderBy('expires_at')
            ->get();

        if ($expiredEarns->isEmpty()) {
            $this->info('No earn rows to expire.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$expiredEarns->count()} earn row(s) to tombstone.");

        $count = 0;
        $totalPoints = 0;
        $morphClass = (new LoyaltyPoint)->getMorphClass();

        foreach ($expiredEarns as $earn) {
            if ($dryRun) {
                $this->line("  Would expire: row #{$earn->id} (patient {$earn->patient_id}) — {$earn->points} pts, expired {$earn->expires_at}");
            } else {
                DB::transaction(function () use ($earn, $morphClass) {
                    LoyaltyPoint::create([
                        'patient_id'    => $earn->patient_id,
                        'points'        => -abs($earn->points),
                        'type'          => LoyaltyPoint::TYPE_EXPIRE,
                        'description'   => 'Points expired (earned ' . ($earn->created_at?->toDateString() ?? 'unknown') . ')',
                        'reference_type' => $morphClass,
                        'reference_id'  => $earn->id,
                        // Mirror source expiry so this tombstone is also filtered
                        // out of balance() — purely an audit/history entry.
                        'expires_at'    => $earn->expires_at,
                    ]);
                });
                $count++;
                $totalPoints += abs($earn->points);
            }
        }

        if (!$dryRun && $count > 0) {
            AuditLogger::log('loyalty_expiry_processed', null, [
                'rows'         => $count,
                'total_points' => $totalPoints,
            ]);
            $this->info("✓ Tombstoned {$count} expired earn row(s) — {$totalPoints} pts total.");
        }

        return self::SUCCESS;
    }
}

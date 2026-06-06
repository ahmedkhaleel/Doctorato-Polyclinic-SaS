<?php

namespace App\Console\Commands;

use App\Services\Pricing\PricingSettingsMirror;
use Illuminate\Console\Command;

/**
 * ADR-001 Phase 1 (EXPAND) — copy each legacy module's current consultation
 * fees from the global `Setting` store into `module_settings`, so the new store
 * is populated. Read path is UNCHANGED by running this (PricingResolver prefers
 * a positive module_settings value but falls back to legacy otherwise), so
 * resolved prices stay identical — verify with `pricing:audit --json` before/after.
 *
 *   php artisan pricing:backfill-module-settings --dry-run
 *   php artisan pricing:backfill-module-settings
 *
 * Idempotent. MANUAL (not an auto-migration) so the owner runs it deliberately.
 */
class PricingBackfillModuleSettingsCommand extends Command
{
    protected $signature = 'pricing:backfill-module-settings {--dry-run : Show what would be written without writing}';

    protected $description = 'ADR-001 Phase 1: backfill legacy module fees into module_settings (idempotent, read-path unchanged)';

    public function handle(PricingSettingsMirror $mirror): int
    {
        $dry = (bool) $this->option('dry-run');
        $rows = $mirror->mirror($dry);

        $this->table(['Module', 'module_settings key', 'from legacy', 'value'], $rows);

        if ($dry) {
            $this->comment('Dry run — nothing written. Re-run without --dry-run to apply.');
        } else {
            $this->info('Backfilled '.count($rows).' module_settings fee values.');
            $this->comment('Read path is UNCHANGED. Verify: `pricing:audit --json` must match the pre-backfill capture.');
        }

        return self::SUCCESS;
    }
}

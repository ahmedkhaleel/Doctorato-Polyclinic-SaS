<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ADR-001 Phase 1 (EXPAND) — copy each legacy module's current consultation
 * fees from the global `Setting` store into `module_settings`, so the new store
 * is fully populated BEFORE the read path is flipped (Phase 2).
 *
 * Safe + reversible + idempotent: this ONLY writes module_settings rows. The
 * PricingResolver still reads the legacy store, so resolved prices are
 * unchanged by running this. Run `pricing:audit --json` before and after — the
 * output must be IDENTICAL (this proves the backfill is a no-op for reads).
 *
 *   php artisan pricing:backfill-module-settings --dry-run
 *   php artisan pricing:backfill-module-settings
 *
 * This is a MANUAL command (not an auto-migration) so the owner runs it
 * deliberately and gates Phase 2 on a clean before/after audit diff.
 */
class PricingBackfillModuleSettingsCommand extends Command
{
    protected $signature = 'pricing:backfill-module-settings {--dry-run : Show what would be written without writing}';

    protected $description = 'ADR-001 Phase 1: backfill legacy module fees into module_settings (idempotent, read-path unchanged)';

    /**
     * module → [module_settings key => legacy Setting key]. Mirrors
     * PricingResolver::source() exactly for the `settings`-driver modules.
     */
    private const MAP = [
        'derma' => [
            'consultant_fee' => 'dermatology_consultant_fee',
            'specialist_fee' => 'dermatology_specialist_fee',
            'consultation_fee' => 'default_dermatology_fee',
            'followup_fee' => 'followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
        'cosmetic' => [
            'consultant_fee' => 'cosmetic_consultation_fee',
            'specialist_fee' => 'cosmetic_consultation_fee',
            'consultation_fee' => 'cosmetic_consultation_fee',
            'followup_fee' => 'followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
        'dental' => [
            'consultant_fee' => 'dental_consultant_fee',
            'specialist_fee' => 'dental_specialist_fee',
            'consultation_fee' => 'dental_consultant_fee',
            'followup_fee' => 'followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
        'pediatric' => [
            'consultant_fee' => 'pediatric_consultant_fee',
            'specialist_fee' => 'pediatric_specialist_fee',
            'consultation_fee' => 'pediatric_consultant_fee',
            'followup_fee' => 'pediatric_followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $written = 0;
        $rows = [];

        foreach (self::MAP as $module => $keys) {
            foreach ($keys as $targetKey => $legacyKey) {
                // Window default mirrors the resolver's legacy default (15).
                $default = $targetKey === 'followup_window_days' ? 15 : 0;
                $value = Setting::get($legacyKey, $default);

                $rows[] = [$module, $targetKey, "← {$legacyKey}", (string) $value];

                if (! $dry) {
                    // upsert (NOT ModuleManager::setSetting, which only UPDATEs —
                    // legacy modules have no module_settings fee rows yet).
                    DB::table('module_settings')->updateOrInsert(
                        ['module' => $module, 'key' => $targetKey],
                        [
                            'value' => (string) $value,
                            'type' => $targetKey === 'followup_window_days' ? 'number' : 'price',
                            'group' => 'pricing',
                            'updated_at' => now(),
                        ],
                    );
                    $written++;
                }
            }
        }

        $this->table(['Module', 'module_settings key', 'from legacy', 'value'], $rows);

        if ($dry) {
            $this->comment('Dry run — nothing written. Re-run without --dry-run to apply.');
        } else {
            ModuleManager::clearCache();
            $this->info("Backfilled {$written} module_settings fee values.");
            $this->comment('Read path is UNCHANGED. Verify: `pricing:audit --json` must match the pre-backfill capture.');
        }

        return self::SUCCESS;
    }
}

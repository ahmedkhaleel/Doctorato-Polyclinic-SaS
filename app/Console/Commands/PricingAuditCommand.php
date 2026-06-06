<?php

namespace App\Console\Commands;

use App\Services\ModuleManager;
use App\Services\Pricing\PricingResolver;
use Illuminate\Console\Command;

/**
 * ADR-001 Phase 0 — read-only snapshot of the resolved consultation pricing for
 * every medical module (consultant / specialist / base / follow-up fee + window).
 * Run before AND after each pricing-unification phase and diff the output: any
 * change means a phase altered a real price (a regression). Reads nothing
 * sensitive and writes nothing.
 *
 *   php artisan pricing:audit            # human table
 *   php artisan pricing:audit --json     # machine-diffable JSON
 */
class PricingAuditCommand extends Command
{
    protected $signature = 'pricing:audit {--json : Output JSON for diffing}';

    protected $description = 'Print the resolved consultation pricing table for all medical modules (read-only)';

    public function handle(PricingResolver $resolver): int
    {
        // The six medical modules + cosmetic (a derma sub-pricing surface).
        $modules = array_merge(ModuleManager::MEDICAL_MODULES, ['cosmetic']);

        $rows = [];
        $payload = [];
        foreach ($modules as $module) {
            $f = $resolver->feesFor($module);
            $rows[] = [
                $module,
                number_format((float) $f['consultant'], 2),
                number_format((float) $f['specialist'], 2),
                number_format((float) $f['base'], 2),
                number_format((float) $f['followup'], 2),
                (int) $f['window'],
            ];
            $payload[$module] = [
                'consultant' => (float) $f['consultant'],
                'specialist' => (float) $f['specialist'],
                'base' => (float) $f['base'],
                'followup' => (float) $f['followup'],
                'window' => (int) $f['window'],
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Module', 'Consultant', 'Specialist', 'Base', 'Follow-up', 'Window (d)'], $rows);
        $this->comment('Read-only. Capture this before/after each ADR-001 phase and diff.');

        return self::SUCCESS;
    }
}

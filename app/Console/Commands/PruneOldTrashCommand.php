<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Permanently delete soft-deleted records older than --days days.
 * Default: 90 days. Lets the trash table stop growing forever.
 *
 * Patient + Doctor are excluded by default — losing those rows breaks
 * historical visit/invoice references. Use --include-patients /
 * --include-doctors if you really want.
 *
 * Idempotent + dry-run friendly.
 */
class PruneOldTrashCommand extends Command
{
    protected $signature = 'trash:prune
                            {--days=90 : Force-delete rows trashed more than this many days ago}
                            {--include-patients : Also prune patients (DANGEROUS — breaks visit/invoice history)}
                            {--include-doctors  : Also prune doctors (DANGEROUS — breaks visit history)}
                            {--dry-run : Preview without deleting}';

    protected $description = 'Force-delete soft-deleted records older than N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $models = [
            'bookings'      => Booking::class,
            'visits'        => Visit::class,
            'invoices'      => Invoice::class,
            'payments'      => Payment::class,
            'prescriptions' => Prescription::class,
        ];

        if ($this->option('include-patients')) $models['patients'] = Patient::class;
        if ($this->option('include-doctors'))  $models['doctors']  = Doctor::class;

        $totals = [];
        foreach ($models as $key => $cls) {
            if (! method_exists($cls, 'onlyTrashed')) {
                $totals[$key] = 0;
                continue;
            }
            $q = $cls::onlyTrashed()->where('deleted_at', '<', $cutoff);
            $count = $q->count();
            $totals[$key] = $count;

            if ($count === 0 || $this->option('dry-run')) continue;

            $q->forceDelete();
        }

        $verb = $this->option('dry-run') ? 'Would prune' : 'Pruned';
        $rows = array_filter($totals);

        if (empty($rows)) {
            $this->info("✓ Nothing trashed older than {$days} days.");
            return self::SUCCESS;
        }

        $this->warn("{$verb} (older than {$days} days):");
        foreach ($rows as $type => $count) {
            $this->line("  • {$type}: {$count}");
        }

        if (! $this->option('dry-run')) {
            Log::info('[trash:prune] removed', ['days' => $days, 'totals' => $totals]);
        }

        return self::SUCCESS;
    }
}

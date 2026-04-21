<?php

namespace App\Console\Commands;

use App\Models\OnlineConsultation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Cleans up online consultations that never completed payment.
 *
 * An OnlineConsultation row is created the moment a patient picks a slot
 * — *before* the payment gateway is hit. If the gateway is misconfigured
 * or the patient abandons checkout, the row sits there forever in
 * payment_status=pending and the slot looks unavailable to other
 * patients until scheduled_date passes.
 *
 * This command deletes (soft-delete if supported, else hard) all such
 * rows older than --older-than (default 24h), freeing the slot.
 *
 * Safe to run repeatedly. Verbose log line for each deleted row.
 *
 * Schedule: hourly at :10.
 */
class CleanupStuckConsultationsCommand extends Command
{
    protected $signature = 'telemedicine:cleanup-stuck
                            {--older-than=24 : Age threshold in hours}
                            {--dry-run : List affected rows without deleting}';

    protected $description = 'Remove OnlineConsultation rows stuck at payment_status=pending';

    public function handle(): int
    {
        $hours = (int) $this->option('older-than');
        $cutoff = now()->subHours($hours);

        $query = OnlineConsultation::where('payment_status', 'pending')
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info("No stuck consultations older than {$hours}h.");
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $rows = $query->get(['id', 'patient_id', 'doctor_id', 'scheduled_date', 'created_at']);
            $this->warn("Would delete {$count} stuck consultation(s):");
            $this->table(
                ['ID', 'Patient', 'Doctor', 'Scheduled', 'Created'],
                $rows->map(fn ($r) => [$r->id, $r->patient_id, $r->doctor_id, $r->scheduled_date, $r->created_at])->toArray()
            );
            return self::SUCCESS;
        }

        // Collect IDs before deletion for logging
        $ids = $query->pluck('id')->toArray();
        $deleted = $query->delete();

        Log::info('[telemedicine:cleanup-stuck] removed stuck consultations', [
            'count' => $deleted,
            'ids'   => $ids,
            'older_than_hours' => $hours,
        ]);

        $this->info("✓ Cleaned up {$deleted} stuck consultation(s).");
        return self::SUCCESS;
    }
}

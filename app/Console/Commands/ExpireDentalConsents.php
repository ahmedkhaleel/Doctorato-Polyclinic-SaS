<?php

namespace App\Console\Commands;

use App\Models\TreatmentPlanConsent;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class ExpireDentalConsents extends Command
{
    protected $signature = 'dental:expire-consents {--dry-run : Show what would be expired without changing anything}';

    protected $description = 'Mark pending treatment plan consents as expired when their expires_at date has passed';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $expiredConsents = TreatmentPlanConsent::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with(['patient:id,full_name', 'treatmentPlan:id,title'])
            ->get();

        if ($expiredConsents->isEmpty()) {
            $this->info('No pending consents to expire.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$expiredConsents->count()} consent(s) to expire.");

        $count = 0;
        foreach ($expiredConsents as $consent) {
            $patientName = $consent->patient?->full_name ?? "Patient #{$consent->patient_id}";
            $planTitle = $consent->treatmentPlan?->title ?? "Plan #{$consent->dental_treatment_plan_id}";

            if ($dryRun) {
                $this->line("  Would expire: Consent #{$consent->id} — {$patientName} / {$planTitle} (expired: {$consent->expires_at})");
            } else {
                $consent->update(['status' => 'expired']);

                AuditLogger::log('updated', $consent, [
                    'old' => ['status' => 'pending'],
                    'new' => ['status' => 'expired'],
                ], "Auto-expired consent for \"{$patientName}\" — plan \"{$planTitle}\"");

                $this->line("  Expired: Consent #{$consent->id} — {$patientName} / {$planTitle}");
                $count++;
            }
        }

        if (!$dryRun) {
            $this->info("Done. {$count} consent(s) marked as expired.");
        }

        return self::SUCCESS;
    }
}

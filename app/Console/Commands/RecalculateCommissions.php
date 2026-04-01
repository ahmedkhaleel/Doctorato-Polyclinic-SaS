<?php

namespace App\Console\Commands;

use App\Models\BookingService;
use App\Models\Invoice;
use App\Models\Visit;
use App\Services\CommissionCalculator;
use Illuminate\Console\Command;

class RecalculateCommissions extends Command
{
    protected $signature = 'visits:recalculate-commissions
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Recalculate commission for completed visits that have 0 commission';

    public function handle(CommissionCalculator $calculator): int
    {
        $dryRun = $this->option('dry-run');

        $visits = Visit::with(['doctor', 'service', 'invoice'])
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereNull('commission_amount')
                    ->orWhere('commission_amount', 0);
            })
            ->whereNotNull('doctor_id')
            ->get();

        $this->info("Found {$visits->count()} completed visits with 0 commission.");

        $updated = 0;

        foreach ($visits as $visit) {
            $doctor = $visit->doctor;

            if (!$doctor) {
                $this->warn("  Visit #{$visit->id}: No doctor found — skipping.");
                continue;
            }

            // Determine paid amount
            $paidAmount = 0;

            if ($visit->invoice) {
                $paidAmount = (float) $visit->invoice->total;
            } elseif ($visit->booking_id && $visit->visit_type === 'consultation') {
                // Booking-linked consultation — check booking invoice or type-specific fee
                $bookingInvoice = Invoice::where('booking_id', $visit->booking_id)->first();
                $paidAmount = $bookingInvoice
                    ? (float) $bookingInvoice->total
                    : $calculator->getConsultationFee($doctor, $visit->consultation_type);
            } elseif ($visit->booking_id && $visit->service_id) {
                // Booking-linked service session — per-session price from BookingService
                $bookingService = BookingService::where('booking_id', $visit->booking_id)
                    ->where('service_id', $visit->service_id)
                    ->first();
                if ($bookingService) {
                    $paidAmount = (float) ($bookingService->unit_price - ($bookingService->discount_per_session ?? 0));
                } elseif ($visit->service) {
                    $paidAmount = (float) ($visit->service->price_after_discount ?? $visit->service->price ?? 0);
                }
            } elseif ($visit->visit_type === 'consultation') {
                $paidAmount = $calculator->getConsultationFee($doctor, $visit->consultation_type);
            } elseif ($visit->service) {
                $paidAmount = (float) ($visit->service->price_after_discount ?? $visit->service->price ?? 0);
            }

            if ($paidAmount <= 0) {
                $this->line("  Visit #{$visit->id} ({$visit->visit_type}): paid_amount=0 — skipping.");
                continue;
            }

            $commission = $calculator->calculate($visit, $paidAmount);

            if ($commission['commission_amount'] > 0) {
                $label = $dryRun ? '[DRY RUN]' : '[UPDATED]';
                $this->info("  {$label} Visit #{$visit->id} ({$visit->visit_type}) — Doctor: {$doctor->name_en} — Amount: {$paidAmount} × {$commission['commission_rate']}% = {$commission['commission_amount']}");

                if (!$dryRun) {
                    $visit->commission_rate = $commission['commission_rate'];
                    $visit->commission_amount = $commission['commission_amount'];
                    $visit->save();
                }

                $updated++;
            } else {
                $this->line("  Visit #{$visit->id} ({$visit->visit_type}): commission still 0 (rate={$commission['commission_rate']}%).");
            }
        }

        $action = $dryRun ? 'Would update' : 'Updated';
        $this->newLine();
        $this->info("{$action} {$updated} out of {$visits->count()} visits.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Models\BookingService;
use App\Models\Invoice;
use App\Models\Visit;
use App\Services\CommissionCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for recalculating doctor commissions in the background.
 * Implements ShouldBeUnique to prevent duplicate runs.
 */
class RecalculateCommissionsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The job may take a while for large datasets.
     */
    public int $timeout = 600; // 10 minutes

    public int $tries = 1;

    /**
     * The unique lock will be released after 15 minutes.
     */
    public int $uniqueFor = 900;

    public function __construct(
        public ?int $visitId = null, // null = recalculate all with 0 commission
    ) {}

    public function handle(CommissionCalculator $calculator): void
    {
        $query = Visit::with(['doctor', 'service', 'invoice'])
            ->where('status', 'completed')
            ->whereNotNull('doctor_id');

        if ($this->visitId) {
            $query->where('id', $this->visitId);
        } else {
            $query->where(function ($q) {
                $q->whereNull('commission_amount')
                    ->orWhere('commission_amount', 0);
            });
        }

        $visits = $query->get();
        $updated = 0;

        foreach ($visits as $visit) {
            $doctor = $visit->doctor;

            if (! $doctor) {
                continue;
            }

            $paidAmount = $this->determinePaidAmount($visit, $calculator);

            if ($paidAmount <= 0) {
                continue;
            }

            $commission = $calculator->calculate($visit, $paidAmount);

            if ($commission['commission_amount'] > 0) {
                $visit->commission_rate = $commission['commission_rate'];
                $visit->commission_amount = $commission['commission_amount'];
                $visit->save();
                $updated++;
            }
        }

        Log::info('Commission recalculation completed', [
            'total_visits' => $visits->count(),
            'updated' => $updated,
            'single_visit' => $this->visitId,
        ]);
    }

    private function determinePaidAmount(Visit $visit, CommissionCalculator $calculator): float
    {
        $doctor = $visit->doctor;

        if ($visit->invoice) {
            return (float) $visit->invoice->total;
        }

        if ($visit->booking_id && $visit->visit_type === 'consultation') {
            $bookingInvoice = Invoice::where('booking_id', $visit->booking_id)->first();

            return $bookingInvoice
                ? (float) $bookingInvoice->total
                : $calculator->getConsultationFee($doctor, $visit->consultation_type);
        }

        if ($visit->booking_id && $visit->service_id) {
            $bookingService = BookingService::where('booking_id', $visit->booking_id)
                ->where('service_id', $visit->service_id)
                ->first();
            if ($bookingService) {
                return (float) ($bookingService->unit_price - ($bookingService->discount_per_session ?? 0));
            }
            if ($visit->service) {
                return (float) ($visit->service->price_after_discount ?? $visit->service->price ?? 0);
            }
        }

        if ($visit->visit_type === 'consultation') {
            return $calculator->getConsultationFee($doctor, $visit->consultation_type);
        }

        if ($visit->service) {
            return (float) ($visit->service->price_after_discount ?? $visit->service->price ?? 0);
        }

        return 0;
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Commission recalculation failed', [
            'visit_id' => $this->visitId,
            'error' => $exception?->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['commission', 'heavy-computation', $this->visitId ? "visit:{$this->visitId}" : 'bulk'];
    }
}

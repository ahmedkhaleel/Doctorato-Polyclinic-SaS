<?php

namespace App\Services;

use App\Events\VisitCancelled as VisitCancelledEvent;
use App\Events\VisitCompleted as VisitCompletedEvent;
use App\Models\DentalTreatment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PackageBundleBookingService;
use App\Models\Setting;
use App\Models\Visit;
use App\Observers\CrmEventObserver;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\DB;

class VisitWorkflowService
{
    public function __construct(
        protected InventoryManager $inventoryManager,
        protected CommissionCalculator $commissionCalculator,
        protected BookingWorkflowService $bookingWorkflowService,
        protected PackageBundleWorkflowService $bundleWorkflowService,
    ) {}

    /**
     * Start a visit (move from waiting to in_progress).
     */
    public function start(Visit $visit): Visit
    {
        $visit->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $visit;
    }

    /**
     * Complete a visit — triggers auto-invoice and inventory deduction.
     */
    public function complete(Visit $visit): array
    {
        $results = ['invoice' => null, 'inventory' => [], 'package_updated' => false];

        DB::transaction(function () use ($visit, &$results) {
            // 1. Mark visit as completed
            $visit->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // 1b. Consume the service's configured supplies from inventory
            // (ServiceSupply links). Idempotent — skipped if already consumed.
            $results['inventory'] = app(ServiceSupplyConsumptionService::class)->consumeForVisit($visit);

            // 2. Update booking tracking if linked
            if ($visit->booking_appointment_id) {
                $this->bookingWorkflowService->handleVisitCompleted($visit);
                $results['booking_updated'] = true;
            }

            // 2c. Update package bundle tracking if linked
            if ($visit->package_bundle_booking_service_id) {
                $this->bundleWorkflowService->handleVisitCompleted($visit);
                $results['bundle_updated'] = true;
            }

            // 3. Auto-generate invoice (skip for booking-linked or bundle-linked visits — invoice already exists)
            if (!$visit->booking_id && !$visit->package_bundle_booking_id) {
                $results['invoice'] = $this->generateInvoice($visit);
            }

            // 4. Calculate doctor commission
            $invoiceTotal = $results['invoice']?->total ?? 0;

            // Determine the paid amount for commission calculation
            $paidAmount = 0;
            if ($invoiceTotal > 0) {
                $paidAmount = (float) $invoiceTotal;
            } elseif ($visit->package_bundle_booking_service_id) {
                // Bundle-linked visit — use bundle_price / sessions_count as the effective price
                $bundleService = PackageBundleBookingService::find($visit->package_bundle_booking_service_id);
                if ($bundleService && $bundleService->sessions_count > 0) {
                    $paidAmount = (float) ($bundleService->bundle_price / $bundleService->sessions_count);
                }
            } elseif ($visit->booking_id && $visit->visit_type === 'consultation') {
                // Booking-linked consultation — use type-specific fee or existing booking invoice
                $bookingInvoice = Invoice::where('booking_id', $visit->booking_id)->first();
                $paidAmount = $bookingInvoice
                    ? (float) $bookingInvoice->total
                    : $this->commissionCalculator->getConsultationFee(
                        $visit->doctor,
                        $visit->consultation_type
                    );
            } elseif ($visit->booking_id && $visit->service_id) {
                // Booking-linked service session — get per-session price from BookingService or Service
                $bookingService = \App\Models\BookingService::where('booking_id', $visit->booking_id)
                    ->where('service_id', $visit->service_id)
                    ->first();

                if ($bookingService) {
                    $paidAmount = (float) ($bookingService->unit_price - ($bookingService->discount_per_session ?? 0));
                } else {
                    $paidAmount = (float) ($visit->service?->price_after_discount ?? $visit->service?->price ?? 0);
                }
            } elseif ($visit->visit_type === 'consultation') {
                // Direct consultation (no booking) — use type-specific fee
                $paidAmount = $this->commissionCalculator->getConsultationFee(
                    $visit->doctor,
                    $visit->consultation_type
                );
            } elseif ($visit->service_id) {
                // Fallback — use service price
                $paidAmount = (float) ($visit->service?->price_after_discount ?? $visit->service?->price ?? 0);
            }

            if ($paidAmount > 0) {
                $commission = $this->commissionCalculator->calculate($visit, $paidAmount);
                $visit->commission_rate = $commission['commission_rate'];
                $visit->commission_amount = $commission['commission_amount'];
                $visit->save();
                $results['commission'] = $commission;
            }

            // 5. Deduct inventory (for service sessions)
            if ($visit->visit_type === 'session' && $visit->service_id) {
                $results['inventory'] = $this->inventoryManager->deductForVisit($visit);
            }
        });

        // 6. CRM: Update linked lead pipeline and scoring
        try {
            CrmEventObserver::onVisitCompleted($visit);
        } catch (\Throwable $e) {
            // CRM integration should not block clinic workflow
            \Illuminate\Support\Facades\Log::warning('CRM visit event failed', ['visit_id' => $visit->id, 'error' => $e->getMessage()]);
        }

        // 7. SMS: Send visit completed notification to patient
        if (Setting::get('sms_on_visit_completed', '0') === '1') {
            try {
                SmsNotificationService::visitCompleted($visit);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('SMS visit completed failed', ['visit_id' => $visit->id, 'error' => $e->getMessage()]);
            }
        }

        // Fire event — listeners handle logging, analytics, etc.
        VisitCompletedEvent::dispatch($visit);

        return $results;
    }

    /**
     * Cancel a visit — reverse inventory if already completed.
     */
    public function cancel(Visit $visit): void
    {
        $wasCompleted = $visit->status === 'completed';

        // Prevent cancellation if visit is in a confirmed or paid payout
        if ($wasCompleted) {
            $activePayout = $visit->payouts()
                ->whereIn('doctor_payouts.status', ['confirmed', 'paid'])
                ->first();

            if ($activePayout) {
                throw new \RuntimeException(
                    'Cannot cancel a visit that is part of a confirmed or paid payout (Payout #' . $activePayout->payout_number . ').'
                );
            }

            // If visit is in a draft payout, remove it and recalculate totals
            $draftPayouts = $visit->payouts()
                ->where('doctor_payouts.status', 'draft')
                ->get();

            foreach ($draftPayouts as $draftPayout) {
                $draftPayout->visits()->detach($visit->id);
                $draftPayout->recalculateTotals();
            }
        }

        DB::transaction(function () use ($visit, $wasCompleted) {
            $visit->update([
                'status' => 'cancelled',
            ]);

            // Reverse inventory deductions if visit was completed
            if ($wasCompleted) {
                $this->inventoryManager->returnForVisit($visit);
            }

            // Revert booking tracking
            if ($visit->booking_appointment_id && $wasCompleted) {
                $this->bookingWorkflowService->handleVisitCancelled($visit);
            }

            // Revert package bundle tracking
            if ($visit->package_bundle_booking_service_id && $wasCompleted) {
                $this->bundleWorkflowService->handleVisitCancelled($visit);
            }
        });

        // CRM: Log no-show / cancellation on linked lead
        try {
            CrmEventObserver::onVisitCancelled($visit);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CRM cancel event failed', ['visit_id' => $visit->id, 'error' => $e->getMessage()]);
        }

        // Fire event
        VisitCancelledEvent::dispatch($visit);
    }

    /**
     * Generate invoice for a completed visit.
     */
    protected function generateInvoice(Visit $visit): Invoice
    {
        $visit->load(['service', 'doctor', 'patient']);

        // Determine price
        if ($visit->visit_type === 'follow_up') {
            if ($visit->module === 'dental') {
                $price = (float) \App\Services\ModuleManager::getSetting('dental', 'followup_fee', 0);
            } elseif ($visit->module === 'pediatric') {
                $price = (float) \App\Services\ModuleManager::getSetting('pediatric', 'followup_fee', 0);
            } else {
                $price = (float) Setting::get('followup_fee', 0);
            }
            $descEn = 'Follow-up Consultation';
            $descAr = 'متابعة كشف';
        } elseif ($visit->visit_type === 'consultation') {
            if ($visit->module === 'dental') {
                $price = (float) \App\Services\ModuleManager::getSetting('dental', 'consultation_fee', 300);
                $descEn = 'Dental Consultation';
                $descAr = 'كشف أسنان';
            } elseif ($visit->module === 'pediatric') {
                $price = $this->commissionCalculator->getConsultationFee(
                    $visit->doctor,
                    'pediatric'
                );
                $descEn = 'Pediatric Consultation';
                $descAr = 'كشف أطفال';
            } else {
                $price = $this->commissionCalculator->getConsultationFee(
                    $visit->doctor,
                    $visit->consultation_type
                );
                if ($visit->consultation_type === 'dermatology') {
                    $descEn = 'Dermatology Consultation';
                    $descAr = 'كشف جلدية';
                } elseif ($visit->consultation_type === 'cosmetic') {
                    $descEn = 'Cosmetic Consultation';
                    $descAr = 'كشف تجميل';
                } else {
                    $descEn = 'Consultation';
                    $descAr = 'كشف';
                }
            }
        } else {
            $price = (float) ($visit->service?->price_after_discount ?? $visit->service?->price ?? 0);
            $descEn = $visit->service?->name_en ?? 'Service Session';
            $descAr = $visit->service?->name_ar ?? 'جلسة خدمة';
        }

        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'module' => $visit->module,
            'subtotal' => $price,
            'total' => $price,
            'created_by' => auth()->id(),
        ]);
        $invoice->status = 'unpaid';
        $invoice->paid_amount = 0;
        $invoice->save();

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description_en' => $descEn,
            'description_ar' => $descAr,
            'quantity' => 1,
            'unit_price' => $price,
            'discount' => 0,
            'total' => $price,
        ]);

        // For dental visits: add completed treatment items and link them to this invoice
        if ($visit->module === 'dental') {
            $dentalInvoiceService = app(DentalInvoiceService::class);
            $completedTreatments = DentalTreatment::where('visit_id', $visit->id)
                ->where('status', 'completed')
                ->whereNull('invoice_id')
                ->get();

            foreach ($completedTreatments as $treatment) {
                $treatment->update(['invoice_id' => $invoice->id]);
                $dentalInvoiceService->generateForCompletedTreatment($treatment);
            }

            // Recalculate invoice totals after adding treatment items
            if ($completedTreatments->isNotEmpty()) {
                $subtotal = $invoice->items()->sum('total');
                $invoice->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal - (float) $invoice->discount_amount + (float) $invoice->tax_amount,
                ]);
            }
        }

        return $invoice;
    }
}

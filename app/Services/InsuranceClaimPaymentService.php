<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

/**
 * Records an insurer's claim reimbursement as a Payment on the linked invoice,
 * so the money actually collected from insurance lands on the invoice balance
 * and in the revenue reports. One Payment per claim, kept in sync with the
 * claim's cumulative paid_amount (handles partial → full), and voided if the
 * claim is rejected/reset. Idempotent + reversible.
 */
class InsuranceClaimPaymentService
{
    /**
     * Sync the claim's reimbursement Payment to its current paid_amount.
     * Creates / updates / removes the Payment as needed, then recalculates
     * the invoice status.
     */
    public function sync(InsuranceClaim $claim): void
    {
        // Nothing to record without a linked invoice.
        if (! $claim->invoice_id) {
            return;
        }

        $amount = (float) $claim->paid_amount;

        DB::transaction(function () use ($claim, $amount) {
            // No (longer any) reimbursement → remove the payment if one exists.
            if ($amount <= 0) {
                $this->reverse($claim);
                return;
            }

            if ($claim->payment_id && ($payment = Payment::find($claim->payment_id))) {
                // Keep the existing reimbursement payment in sync (partial → full).
                if ((float) $payment->amount !== $amount) {
                    $payment->update(['amount' => $amount]);
                }
            } else {
                $payment = Payment::create([
                    'invoice_id'        => $claim->invoice_id,
                    'patient_id'        => $claim->patient_id,
                    'payment_method_id' => $this->insuranceMethodId(),
                    'amount'            => $amount,
                    'payment_date'      => ($claim->paid_at ?? now())->toDateString(),
                    'reference_number'  => $claim->reference_number,
                    'notes'             => 'Insurance reimbursement — claim ' . $claim->claim_number,
                    'received_by'       => auth()->id(),
                ]);
                $claim->forceFill(['payment_id' => $payment->id])->saveQuietly();
            }

            $claim->invoice?->recalculateStatus();
        });
    }

    /** Remove the claim's reimbursement payment (on rejection/reset). */
    public function reverse(InsuranceClaim $claim): void
    {
        if (! $claim->payment_id) {
            return;
        }

        DB::transaction(function () use ($claim) {
            $invoice = $claim->invoice;
            Payment::where('id', $claim->payment_id)->delete();
            $claim->forceFill(['payment_id' => null])->saveQuietly();
            $invoice?->recalculateStatus();
        });
    }

    private function insuranceMethodId(): ?int
    {
        return PaymentMethod::firstOrCreate(
            ['name_en' => 'Insurance'],
            ['name_ar' => 'تأمين', 'is_active' => true]
        )->id;
    }
}

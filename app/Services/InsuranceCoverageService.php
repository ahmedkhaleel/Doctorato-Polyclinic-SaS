<?php

namespace App\Services;

use App\Models\Invoice;

class InsuranceCoverageService
{
    /**
     * Apply insurance to an invoice. If patient has active verified insurance,
     * calculate coverage and populate invoice fields.
     */
    public function applyToInvoice(Invoice $invoice): void
    {
        if ($invoice->patient_insurance_id) {
            return; // already applied
        }

        $patient = $invoice->patient;
        if (!$patient) {
            return;
        }

        $insurance = $patient->activeInsurance;
        if (!$insurance || !$insurance->is_verified) {
            return;
        }

        // Check coverage based on module
        $module = $invoice->module ?? 'derma';
        if ($insurance->plan && !$insurance->plan->coversServiceType($module)) {
            return; // plan doesn't cover this module
        }

        $calc = $insurance->calculatePatientShare((float) $invoice->total);

        $invoice->update([
            'patient_insurance_id' => $insurance->id,
            'insurance_covered' => $calc['covered'] ?? 0,
            'patient_net_amount' => $calc['patient_share'] ?? $invoice->total,
        ]);
    }
}

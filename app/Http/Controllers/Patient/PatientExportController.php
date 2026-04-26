<?php

namespace App\Http\Controllers\Patient;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Self-service "download my full medical record" — patient gets the
 * same PDF that admins can generate from /admin/patients/{id}/export,
 * minus highly-sensitive fields (HIV/Hepatitis/anesthesia notes etc.)
 * which patients can never bulk-export through the portal.
 *
 * Reuses the existing pdf.patient-file template — no new view needed.
 */
class PatientExportController extends BasePatientController
{
    public function downloadFullFile(Request $request)
    {
        $patient = $this->patient($request);

        // Same eager-load set as the admin exporter so the PDF template
        // doesn't fall back to N+1 queries while rendering.
        $patient->load([
            'visits' => fn ($q) => $q->with(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en'])->latest('visit_date'),
            'invoices' => fn ($q) => $q->with('items')->latest('invoice_date'),
            'prescriptions' => fn ($q) => $q->with(['doctor:id,name_ar,name_en', 'items'])->latest(),
            'dentalTreatments' => fn ($q) => $q->with('doctor:id,name_ar,name_en')->latest('completed_at')->latest('created_at'),
            'dentalCharts',
            'dentalXrays' => fn ($q) => $q->with('doctor:id,name_ar,name_en')->latest('taken_date'),
            'pediatricGrowthRecords' => fn ($q) => $q->orderBy('measurement_date'),
            'pediatricVaccinations' => fn ($q) => $q->orderBy('scheduled_date'),
            'pediatricAllergies' => fn ($q) => $q->where('is_active', true),
        ]);

        // Financial summary (same shape the template expects)
        $totalInvoiced = (float) $patient->invoices->sum('total');
        $totalPaid     = (float) $patient->invoices->sum('paid_amount');
        $outstanding   = 0.0;
        foreach ($patient->invoices as $inv) {
            if (in_array($inv->status, ['unpaid', 'partial'])) {
                $outstanding += ((float) $inv->total) - ((float) $inv->paid_amount);
            }
        }

        $data = [
            'patient' => $patient,
            'activeSpecialties' => $patient->getActiveSpecialties(),
            // Patients NEVER get sensitive fields exported — they can see
            // their own non-sensitive history but not the medical-flag
            // bundle (HIV / Hepatitis / blood-thinners / anesthesia notes).
            'canViewSensitive' => false,
            'financialSummary' => [
                'total_invoiced' => round($totalInvoiced, 2),
                'total_paid'     => round($totalPaid, 2),
                'outstanding'    => round($outstanding, 2),
                'invoice_count'  => $patient->invoices->count(),
                'visit_count'    => $patient->visits->count(),
            ],
            'exportedAt' => now(),
            'exportedBy' => $patient->full_name . ' (self-export)',
        ];

        $pdf = Pdf::loadView('pdf.patient-file', $data)->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', (string) $patient->full_name) ?: 'patient';
        $filename = 'medical-record-' . $safeName . '-' . now()->format('Y-m-d') . '.pdf';

        \App\Services\AuditLogger::log('patient_self_export', $patient, [
            'filename' => $filename,
        ]);

        return $pdf->download($filename);
    }
}

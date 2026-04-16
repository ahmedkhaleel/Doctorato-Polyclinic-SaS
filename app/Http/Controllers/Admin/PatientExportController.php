<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PatientExportController extends Controller
{
    /**
     * Export a full patient file as PDF (all specialties, visits, invoices, prescriptions).
     */
    public function exportFullFile(Patient $patient)
    {
        // Eager-load EVERYTHING the PDF template needs in one pass to avoid N+1 queries.
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

        $canViewSensitive = Auth::user()?->can('patients.view_sensitive_medical') ?? false;

        if ($canViewSensitive) {
            $patient->makeVisible(Patient::SENSITIVE_MEDICAL_FIELDS);
        }

        // Financial summary
        $totalInvoiced = (float) $patient->invoices->sum('total');
        $totalPaid = (float) $patient->invoices->sum('paid_amount');
        $outstanding = 0.0;
        foreach ($patient->invoices as $inv) {
            if (in_array($inv->status, ['unpaid', 'partial'])) {
                $outstanding += ((float) $inv->total) - ((float) $inv->paid_amount);
            }
        }

        $data = [
            'patient' => $patient,
            'activeSpecialties' => $patient->getActiveSpecialties(),
            'canViewSensitive' => $canViewSensitive,
            'financialSummary' => [
                'total_invoiced' => round($totalInvoiced, 2),
                'total_paid' => round($totalPaid, 2),
                'outstanding' => round($outstanding, 2),
                'invoice_count' => $patient->invoices->count(),
                'visit_count' => $patient->visits->count(),
            ],
            'exportedAt' => now(),
            'exportedBy' => Auth::user()?->name ?? 'System',
        ];

        $pdf = Pdf::loadView('pdf.patient-file', $data)
            ->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', (string) $patient->full_name) ?: 'patient';
        $filename = 'patient-file-' . $safeName . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

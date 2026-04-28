<?php

namespace App\Http\Controllers\Patient;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PatientInvoiceController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'module' => 'nullable|string|in:derma,dental,pediatric',
        ]);

        $query = Invoice::where('patient_id', $this->patientId($request));

        if ($module = $filters['module'] ?? null) {
            $query->where('module', $module);
        }

        $invoices = $query->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Patient/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'module' => $request->input('module'),
            ],
        ]);
    }

    public function show(Request $request, string $locale, Invoice $invoice): Response
    {
        $this->authorizePatient($request, $invoice);

        $invoice->load([
            'items',
            'payments.paymentMethod:id,name_en,name_ar',
            'visit:id,visit_date',
            'visit.doctor:id,name_en,name_ar',
            'visit.service:id,name_en,name_ar',
        ]);

        return Inertia::render('Patient/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Patient self-service PDF download of their own invoice. Reuses
     * the existing pdf.invoice blade template that the admin/secretary
     * use — no new layout, no duplication.
     *
     * Authorization is enforced via authorizePatient() — patients can
     * never download another patient's invoice even by guessing IDs.
     */
    public function downloadPdf(Request $request, string $locale, Invoice $invoice): HttpResponse
    {
        $this->authorizePatient($request, $invoice);

        $invoice->load([
            'items',
            'payments.paymentMethod',
            'patient',
            'visit.doctor',
            'visit.service',
            'discountCode',
            'creator',
        ]);

        $currency = \App\Models\Setting::get('currency_code', 'EGP');
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'currency'))
            ->setPaper('a4', 'portrait');

        $filename = preg_replace('/[^A-Za-z0-9\-\.]/', '_', $invoice->invoice_number . '.pdf');

        \App\Services\AuditLogger::log('patient_invoice_pdf_downloaded', $invoice, [
            'patient_id' => $this->patientId($request),
        ]);

        return $pdf->download($filename);
    }
}

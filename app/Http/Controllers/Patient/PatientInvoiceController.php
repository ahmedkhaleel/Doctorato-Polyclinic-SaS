<?php

namespace App\Http\Controllers\Patient;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientInvoiceController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'module' => 'nullable|string|in:derma,dental',
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
}

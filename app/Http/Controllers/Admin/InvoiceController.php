<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Invoice::with(['patient', 'visit']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'module', 'date_from', 'date_to']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Invoices/Create', [
            'patients' => Patient::where('is_active', true)->select('id', 'full_name', 'phone', 'file_number')
                ->with(['activeInsurance' => fn ($q) => $q->with(['company:id,name_ar,name_en', 'plan:id,name_ar,name_en,coverage_percentage,copay_amount'])])
                ->get(),
            'services' => Service::active()
                ->whereIn('module', ['derma', 'dental'])
                ->select('id', 'name_ar', 'name_en', 'price', 'price_after_discount', 'module')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'discount_code_id' => 'nullable|exists:discount_codes,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'patient_insurance_id' => 'nullable|exists:patient_insurances,id',
            'insurance_covered' => 'nullable|numeric|min:0',
            'is_installment' => 'nullable|boolean',
            'installment_count' => 'nullable|integer|min:2|max:24',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.description_ar' => 'required|string|max:255',
            'items.*.description_en' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        try {
        $invoice = DB::transaction(function () use ($data) {
            // Calculate totals from items
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += $itemTotal;
            }

            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;

            if ($discountAmount > $subtotal) {
                throw new \RuntimeException('Discount amount cannot exceed the subtotal.');
            }

            $total = $subtotal - $discountAmount + $taxAmount;

            // Insurance calculations
            $insuranceCovered = (float) ($data['insurance_covered'] ?? 0);
            $patientNet = $total - $insuranceCovered;

            // Installment calculations
            $isInstallment = $data['is_installment'] ?? false;
            $installmentCount = $isInstallment ? ($data['installment_count'] ?? 2) : null;
            $installmentAmount = $isInstallment && $installmentCount ? round($patientNet / $installmentCount, 2) : null;

            // Auto-detect module from visit or first service item
            $module = null;
            if (! empty($data['visit_id'])) {
                $visit = \App\Models\Visit::find($data['visit_id']);
                $module = $visit?->module;
            }
            if (! $module && ! empty($data['items'])) {
                $serviceId = collect($data['items'])->pluck('service_id')->filter()->first();
                if ($serviceId) {
                    $module = Service::where('id', $serviceId)->value('module');
                }
            }

            $invoice = new Invoice([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'patient_id' => $data['patient_id'],
                'visit_id' => $data['visit_id'] ?? null,
                'module' => $module,
                'discount_code_id' => $data['discount_code_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'patient_insurance_id' => $data['patient_insurance_id'] ?? null,
                'insurance_covered' => $insuranceCovered,
                'patient_net_amount' => $patientNet,
                'is_installment' => $isInstallment,
                'installment_count' => $installmentCount,
                'installment_amount' => $installmentAmount,
                'next_installment_date' => $isInstallment ? now()->addMonth()->toDateString() : null,
                'created_by' => auth()->id(),
            ]);
            $invoice->paid_amount = 0;
            $invoice->status = 'unpaid';
            $invoice->save();

            foreach ($data['items'] as $item) {
                $itemTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description_ar' => $item['description_ar'],
                    'description_en' => $item['description_en'] ?? $item['description_ar'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $itemTotal,
                ]);
            }

            return $invoice;
        });
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        AuditLogger::log('created', $invoice);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'items',
            'payments.paymentMethod',
            'payments.receiver',
            'patient',
            'visit.doctor',
            'visit.service',
            'packageBundleBooking',
            'discountCode',
            'creator',
        ]);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
            'paymentMethods' => PaymentMethod::active()->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($data);

        AuditLogger::log('updated', $invoice);

        return redirect()->back()->with('success', 'Invoice updated successfully.');
    }

    public function downloadPdf(Invoice $invoice): HttpResponse
    {
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

        $filename = $invoice->invoice_number . '.pdf';
        $filename = preg_replace('/[^A-Za-z0-9\-\.]/', '_', $filename);

        return $pdf->download($filename);
    }

    public function print(Invoice $invoice): Response
    {
        $invoice->load([
            'items',
            'payments.paymentMethod',
            'patient',
            'visit.doctor',
            'visit.service',
            'creator',
        ]);

        return Inertia::render('Admin/Invoices/Print', [
            'invoice' => $invoice,
        ])->withViewData(['layout' => 'none']);
    }

    public function printPaymentReceipt(Invoice $invoice, Payment $payment): Response
    {
        $invoice->load([
            'items',
            'patient',
            'booking',
        ]);

        $payment->load(['paymentMethod', 'receiver']);

        // Get all payments for this invoice to show payment history
        $allPayments = $invoice->payments()
            ->with(['paymentMethod', 'receiver'])
            ->orderBy('payment_date')
            ->get();

        return Inertia::render('Admin/Invoices/PrintPaymentReceipt', [
            'invoice' => $invoice,
            'payment' => $payment,
            'allPayments' => $allPayments,
        ]);
    }
}

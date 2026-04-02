<?php

namespace App\Http\Controllers\Secretary;

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
use Inertia\Inertia;
use Inertia\Response;

class SecretaryInvoiceController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $query = Invoice::with(['patient', 'creator']);

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
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Secretary/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'module']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Secretary/Invoices/Create', [
            'patients' => Patient::where('is_active', true)->select('id', 'full_name', 'phone', 'file_number')->get(),
            'services' => Service::active()->select('id', 'name_ar', 'name_en', 'price', 'price_after_discount')->get(),
            'discountCodes' => DiscountCode::where('is_active', true)->get(),
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
            'items' => 'required|array|min:1',
            'items.*.description_ar' => 'required|string',
            'items.*.description_en' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        $invoice = DB::transaction(function () use ($data) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += $itemTotal;
            }

            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;
            $total = $subtotal - $discountAmount + $taxAmount;

            $invoice = new Invoice([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'patient_id' => $data['patient_id'],
                'visit_id' => $data['visit_id'] ?? null,
                'discount_code_id' => $data['discount_code_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
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

        AuditLogger::log('created', $invoice);

        return redirect()->route('secretary.invoices.show', $invoice)->with('success', $this->msg('Invoice created successfully.', 'تم إنشاء الفاتورة بنجاح.'));
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

        return Inertia::render('Secretary/Invoices/Show', [
            'invoice' => $invoice,
            'paymentMethods' => PaymentMethod::active()->get(),
        ]);
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

        return Inertia::render('Secretary/Invoices/PrintPaymentReceipt', [
            'invoice' => $invoice,
            'payment' => $payment,
            'allPayments' => $allPayments,
        ]);
    }
}

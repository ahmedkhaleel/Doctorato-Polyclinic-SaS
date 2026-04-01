<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Observers\CrmEventObserver;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Payment::with(['invoice.patient', 'paymentMethod', 'receiver']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('patient', function ($pq) use ($search) {
                                $pq->where('full_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('payment_date', '<=', $dateTo);
        }

        if ($module = $request->input('module')) {
            $query->whereHas('invoice', fn ($q) => $q->where('module', $module));
        }

        $payments = $query->latest('payment_date')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'module']),
            'paymentMethods' => PaymentMethod::active()->get(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $payment = DB::transaction(function () use ($data) {
                // Lock the invoice row to prevent concurrent overpayment
                $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);

                // Prevent overpayment
                $balance = $invoice->total - $invoice->paid_amount;
                if ($data['amount'] > $balance) {
                    throw new \RuntimeException(
                        "Amount exceeds remaining balance of " . number_format($balance, 2) . " " . \App\Models\Setting::get('currency_code', 'EGP') . "."
                    );
                }

                $data['patient_id'] = $invoice->patient_id;
                $data['received_by'] = auth()->id();

                $payment = Payment::create($data);

                // Recalculate invoice status after payment
                $invoice->recalculateStatus();

                return $payment;
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors([
                'amount' => $e->getMessage(),
            ])->withInput();
        }

        AuditLogger::log('created', $payment);

        // CRM: Update linked lead scoring and pipeline
        try {
            CrmEventObserver::onPaymentReceived($payment);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CRM payment event failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $invoice = $payment->invoice;

        AuditLogger::log('deleted', $payment);

        $payment->delete();

        // Recalculate invoice status after payment deletion
        $invoice->recalculateStatus();

        return redirect()->back()->with('success', 'Payment deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Observers\CrmEventObserver;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryPaymentController extends BaseSecretaryController
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

        $payments = $query->latest('payment_date')->paginate(15)->withQueryString();

        return Inertia::render('Secretary/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'date_from', 'date_to']),
            'paymentMethods' => PaymentMethod::active()->get(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $payment = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
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

                // Recalculate invoice status
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

        return redirect()->back()->with('success', $this->msg('Payment recorded successfully.', 'تم تسجيل الدفعة بنجاح.'));
    }
}

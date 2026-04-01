<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use Illuminate\Support\Facades\Log;

class LogPaymentReceived
{
    public function handle(PaymentReceived $event): void
    {
        Log::info('Payment received', [
            'payment_id' => $event->payment->id,
            'invoice_id' => $event->invoice->id,
            'patient_id' => $event->payment->patient_id,
            'amount' => $event->payment->amount,
            'invoice_status' => $event->invoice->status,
        ]);
    }
}

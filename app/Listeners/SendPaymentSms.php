<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentSms implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;
        $invoice = $event->invoice;
        $patient = $payment->patient;

        if (! $patient?->phone) {
            return;
        }

        $remaining = max(0, $invoice->total - $invoice->paid_amount);
        $clinicName = Setting::get('clinic_name_ar', 'العيادة');

        $message = "عزيزي/تي {$patient->full_name}،\n"
            ."تم استلام دفعة بقيمة {$payment->amount} ر.س بنجاح.\n";

        if ($remaining > 0) {
            $message .= "المتبقي: {$remaining} ر.س\n";
        } else {
            $message .= "تم سداد الفاتورة بالكامل.\n";
        }

        $message .= "شكراً لكم — {$clinicName}";

        // payment.received is transactional → always sends through the hub
        // (sms via legacy fallback, whatsapp if enabled, in_app always logged).
        Notifier::event('payment.received', $patient, [
            'to' => $patient->phone,
            'body' => $message,
            'name' => $patient->full_name,
            'amount' => $payment->amount,
            'clinic_name' => $clinicName,
        ]);
    }
}

<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Jobs\SendSmsJob;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentSms implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        if (Setting::get('sms_enabled') !== '1') {
            return;
        }

        $payment = $event->payment;
        $invoice = $event->invoice;
        $patient = $payment->patient;

        if (! $patient?->phone) {
            return;
        }

        $remaining = max(0, $invoice->total - $invoice->paid_amount);
        $clinicName = Setting::get('clinic_name_ar', 'العيادة');

        $message = "عزيزي/تي {$patient->full_name}،\n"
            . "تم استلام دفعة بقيمة {$payment->amount} ر.س بنجاح.\n";

        if ($remaining > 0) {
            $message .= "المتبقي: {$remaining} ر.س\n";
        } else {
            $message .= "تم سداد الفاتورة بالكامل.\n";
        }

        $message .= "شكراً لكم — {$clinicName}";

        SendSmsJob::dispatch($patient->phone, $message);
    }
}

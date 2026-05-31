<?php

namespace App\Listeners;

use App\Events\InvoiceOverdue;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notifications\Notifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class SendOverdueReminder implements ShouldQueue
{
    public function handle(InvoiceOverdue $event): void
    {
        $invoice = $event->invoice;
        $patient = $invoice->patient;
        $remaining = $invoice->total - $invoice->paid_amount;

        // 1) Patient reminder via the unified hub (transactional → always sends;
        //    sms via legacy fallback, email/whatsapp if configured, in_app logged).
        if ($patient?->phone) {
            $clinicName = Setting::get('clinic_name_ar', 'العيادة');
            $message = "عزيزي/تي {$patient->full_name}،\n"
                ."نذكّركم بوجود مبلغ مستحق بقيمة {$remaining} ر.س (فاتورة #{$invoice->invoice_number}).\n"
                ."يرجى مراجعة الاستقبال للسداد.\n"
                ."شكراً — {$clinicName}";

            Notifier::event('invoice.overdue', $patient, [
                'to' => $patient->phone,
                'body' => $message,
                'subject' => "فاتورة مستحقة #{$invoice->invoice_number}",
                'name' => $patient->full_name,
                'amount' => $remaining,
                'clinic_name' => $clinicName,
            ]);
        }

        // 2) In-app notification to admins
        $admins = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            DatabaseNotification::create([
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\InvoiceOverdueNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => [
                    'type' => 'invoice_overdue',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'patient_name' => $patient?->full_name ?? 'Unknown',
                    'amount' => $remaining,
                    'days_past_due' => $event->daysPastDue,
                    'message' => "Invoice #{$invoice->invoice_number} is {$event->daysPastDue} days overdue ({$remaining} SAR)",
                ],
            ]);
        }
    }
}

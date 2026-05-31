<?php

namespace Tests\Feature\Notifications;

use App\Events\InvoiceOverdue;
use App\Events\PaymentReceived;
use App\Listeners\SendOverdueReminder;
use App\Listeners\SendPaymentSms;
use App\Models\Invoice;
use App\Models\NotificationEvent;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentInvoiceLoyaltyNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function legacySms(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Pay Patient', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-PIL-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs);
        $p->save();

        return $p;
    }

    private function invoice(Patient $patient, array $attrs = []): Invoice
    {
        return Invoice::create(array_merge([
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-'.uniqid(),
            'total' => 500, 'paid_amount' => 0,
            'status' => 'unpaid', 'invoice_date' => now()->toDateString(),
        ], $attrs));
    }

    public function test_payment_received_routes_through_hub(): void
    {
        $this->legacySms();
        $patient = $this->patient();
        $invoice = $this->invoice($patient, ['paid_amount' => 500, 'status' => 'paid']);
        $payment = Payment::create([
            'invoice_id' => $invoice->id, 'patient_id' => $patient->id,
            'amount' => 500, 'payment_date' => now()->toDateString(),
        ]);

        (new SendPaymentSms)->handle(new PaymentReceived($payment, $invoice));

        $log = NotificationLog::where('event_key', 'payment.received')->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
        $this->assertSame($patient->id, $log->recipient_id);
    }

    public function test_overdue_invoice_routes_through_hub(): void
    {
        $this->legacySms();
        $patient = $this->patient();
        $invoice = $this->invoice($patient, ['total' => 300, 'paid_amount' => 0]);

        (new SendOverdueReminder)->handle(new InvoiceOverdue($invoice, 10));

        $log = NotificationLog::where('event_key', 'invoice.overdue')->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
    }

    public function test_loyalty_earned_is_marketing_and_respects_consent(): void
    {
        $this->legacySms();
        // Event category aligned to marketing by migration.
        $this->assertSame('marketing', NotificationEvent::where('key', 'loyalty.earned')->value('category'));

        // Default: marketing opt-OUT → skipped.
        $optedOut = $this->patient();
        $logs = Notifier::eventNow('loyalty.earned', $optedOut, ['body' => 'نقاط', 'to' => $optedOut->phone]);
        $sms = collect($logs)->firstWhere('channel', 'sms');
        $this->assertSame(NotificationLog::STATUS_SKIPPED, $sms->status);

        // Opted in → sent.
        $optedIn = $this->patient(['notify_sms_marketing' => true]);
        $logs2 = Notifier::eventNow('loyalty.earned', $optedIn, ['body' => 'نقاط', 'to' => $optedIn->phone]);
        $sms2 = collect($logs2)->firstWhere('channel', 'sms');
        $this->assertSame(NotificationLog::STATUS_SENT, $sms2->status);
    }
}

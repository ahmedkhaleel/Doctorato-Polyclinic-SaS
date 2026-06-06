<?php

namespace Tests\Feature\Security;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\OnlineConsultation;
use App\Models\Patient;
use App\Services\OnlineConsultationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-4 — payment reconciliation guarantees:
 *  - Marking a consultation paid twice (e.g. a retried gateway webhook) records
 *    income exactly once — no double invoice / double payment.
 *  - The integrity check flags an overpaid invoice (double-counted payment).
 */
class PaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_a_consultation_paid_twice_records_income_once(): void
    {
        $patient = Patient::create(['full_name' => 'Pay Once', 'phone' => '0500111222']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PAY-1'])->save();

        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'psychiatry']);

        $consultation = OnlineConsultation::create([
            'consultation_number' => 'OC-'.uniqid(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'module' => 'telemedicine',
            'scheduled_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'scheduled',
            'fee' => 300,
            'payment_status' => 'pending',
        ]);

        $svc = app(OnlineConsultationService::class);

        // First webhook.
        $svc->markPaid($consultation->fresh(), 'GW-REF-1');
        // Duplicate/retried webhook (the call site guards on payment_status,
        // and recordConsultationIncome short-circuits on a linked invoice).
        $c = $consultation->fresh();
        if ($c->payment_status !== 'paid') {
            $svc->markPaid($c, 'GW-REF-1');
        }
        // Even a forced second call must not double-record.
        $svc->markPaid($consultation->fresh(), 'GW-REF-1');

        $this->assertSame(1, Invoice::where('patient_id', $patient->id)->count(), 'exactly one invoice');
        $invoice = Invoice::where('patient_id', $patient->id)->first();
        $this->assertEqualsWithDelta(300, (float) $invoice->total, 0.01);
        $this->assertEqualsWithDelta(300, (float) $invoice->paid_amount, 0.01, 'paid_amount must not double');
        $this->assertSame(1, $invoice->payments()->count(), 'exactly one payment');
    }

    public function test_integrity_check_flags_an_overpaid_invoice(): void
    {
        $patient = Patient::create(['full_name' => 'Overpaid', 'phone' => '0500333444']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-OVR-1'])->save();

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'subtotal' => 100, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => 100,
        ]);
        // Simulate a double-counted payment by forcing paid_amount past total.
        $invoice->forceFill(['paid_amount' => 200])->save();

        // The command exits non-zero when it finds any integrity issue.
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('invoice_overpaid')
            ->assertExitCode(1);
    }
}

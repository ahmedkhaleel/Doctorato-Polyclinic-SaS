<?php

namespace Tests\Feature\Obgyn;

use App\Models\AntenatalVisit;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Services\ObgynBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pins the OB/GYN money-flow: a billable encounter produces a Visit
 * (module=obgyn) + a module-tagged Invoice + InvoiceItem, idempotently,
 * and is reversible.
 */
class ObgynBillingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAnc(float $fee = 150): AntenatalVisit
    {
        $patient = Patient::create(['full_name' => 'Mom', 'phone' => '0100', 'gender' => 'female']);
        $pregnancy = Pregnancy::create(['patient_id' => $patient->id, 'status' => Pregnancy::STATUS_ACTIVE]);

        return AntenatalVisit::create([
            'pregnancy_id' => $pregnancy->id,
            'visit_date' => '2026-05-01',
            'gestational_age_weeks' => 28,
        ]);
    }

    #[Test]
    public function billing_an_anc_visit_creates_a_module_tagged_invoice_and_item(): void
    {
        $anc = $this->makeAnc();

        $invoice = app(ObgynBillingService::class)->billAntenatalVisit($anc, 150);
        $anc->refresh();

        $this->assertNotNull($invoice);
        $this->assertSame('obgyn', $invoice->module);
        $this->assertEquals(150, (float) $invoice->total);

        // Encounter linked to the invoice + item.
        $this->assertNotNull($anc->invoice_id);
        $this->assertNotNull($anc->invoice_item_id);
        $this->assertCount(1, $invoice->items);
    }

    #[Test]
    public function billing_is_idempotent(): void
    {
        $anc = $this->makeAnc();
        $svc = app(ObgynBillingService::class);

        $svc->billAntenatalVisit($anc, 150);
        $svc->billAntenatalVisit($anc->refresh(), 150);

        $this->assertSame(1, Invoice::where('module', 'obgyn')->count());
        $this->assertSame(1, $anc->refresh()->invoice->items()->count());
    }

    #[Test]
    public function zero_fee_is_not_billed(): void
    {
        $anc = $this->makeAnc();
        $invoice = app(ObgynBillingService::class)->billAntenatalVisit($anc, 0);

        $this->assertNull($invoice);
        $this->assertNull($anc->refresh()->invoice_item_id);
    }

    #[Test]
    public function reversing_voids_the_line_and_clears_links(): void
    {
        $anc = $this->makeAnc();
        $svc = app(ObgynBillingService::class);

        $invoice = $svc->billAntenatalVisit($anc, 150);
        $svc->reverse($anc->refresh());

        $this->assertNull($anc->refresh()->invoice_item_id);
        $this->assertEquals(0, (float) $invoice->refresh()->total);
        $this->assertSame(0, $invoice->items()->count());
    }

    #[Test]
    public function fee_falls_back_to_module_settings(): void
    {
        // anc_fee seeded at 150 by migration.
        $anc = $this->makeAnc();
        $invoice = app(ObgynBillingService::class)->billAntenatalVisit($anc);

        $this->assertEquals(150, (float) $invoice->total);
    }
}

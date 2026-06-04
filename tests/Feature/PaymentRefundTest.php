<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\Payment\Drivers\PaymobGateway;
use App\Services\Payment\Drivers\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the gateway refund() implementations (Stripe + Paymob). The external
 * HTTP calls are faked, so these assert our logic: only succeeded charges are
 * refundable, success marks the transaction 'refunded', and API failures leave
 * the transaction untouched (returns false).
 */
class PaymentRefundTest extends TestCase
{
    use RefreshDatabase;

    private function txn(string $gateway, array $overrides = []): PaymentTransaction
    {
        return PaymentTransaction::create(array_merge([
            'gateway' => $gateway,
            'gateway_reference' => $gateway === 'stripe' ? 'pi_test_123' : '987654',
            'amount' => 100.00,
            'currency' => 'EGP',
            'status' => 'succeeded',
            'type' => 'payment',
        ], $overrides));
    }

    // ─── Stripe ─────────────────────────────────────────────────

    public function test_stripe_refund_marks_transaction_refunded_on_success(): void
    {
        Setting::set('stripe_secret_key', 'sk_test_x');
        Http::fake(['api.stripe.com/v1/refunds' => Http::response(['id' => 're_1', 'status' => 'succeeded'], 200)]);

        $txn = $this->txn('stripe');
        $ok = (new StripeGateway)->refund($txn);

        $this->assertTrue($ok);
        $this->assertSame('refunded', $txn->fresh()->status);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/refunds')
            && $r['payment_intent'] === 'pi_test_123');
    }

    public function test_stripe_partial_refund_sends_amount_in_cents(): void
    {
        Setting::set('stripe_secret_key', 'sk_test_x');
        Http::fake(['api.stripe.com/v1/refunds' => Http::response(['id' => 're_2'], 200)]);

        $ok = (new StripeGateway)->refund($this->txn('stripe'), 40.50);

        $this->assertTrue($ok);
        Http::assertSent(fn ($r) => (string) $r['amount'] === '4050');
    }

    public function test_stripe_refund_returns_false_on_api_error(): void
    {
        Setting::set('stripe_secret_key', 'sk_test_x');
        Http::fake(['api.stripe.com/v1/refunds' => Http::response(['error' => 'nope'], 400)]);

        $txn = $this->txn('stripe');
        $this->assertFalse((new StripeGateway)->refund($txn));
        $this->assertSame('succeeded', $txn->fresh()->status, 'failed refund must not flip status');
    }

    public function test_stripe_refund_refuses_non_succeeded_transaction(): void
    {
        Setting::set('stripe_secret_key', 'sk_test_x');
        Http::fake();

        $txn = $this->txn('stripe', ['status' => 'pending']);
        $this->assertFalse((new StripeGateway)->refund($txn));
        Http::assertNothingSent();
    }

    // ─── Paymob ─────────────────────────────────────────────────

    public function test_paymob_refund_authenticates_then_refunds(): void
    {
        Setting::set('paymob_api_key', 'pmk_test');
        Http::fake([
            '*/auth/tokens' => Http::response(['token' => 'tok_123'], 200),
            '*/acceptance/void_refund/refund' => Http::response(['id' => 99, 'success' => true], 200),
        ]);

        $txn = $this->txn('paymob');
        $ok = (new PaymobGateway)->refund($txn);

        $this->assertTrue($ok);
        $this->assertSame('refunded', $txn->fresh()->status);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'void_refund/refund')
            && (string) $r['transaction_id'] === '987654'
            && (string) $r['amount_cents'] === '10000');
    }

    public function test_paymob_refund_returns_false_when_auth_fails(): void
    {
        Setting::set('paymob_api_key', 'pmk_test');
        Http::fake(['*/auth/tokens' => Http::response(['message' => 'bad key'], 401)]);

        $txn = $this->txn('paymob');
        $this->assertFalse((new PaymobGateway)->refund($txn));
        $this->assertSame('succeeded', $txn->fresh()->status);
    }
}

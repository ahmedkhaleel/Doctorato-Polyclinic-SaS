<?php

namespace App\Services\Payment\Drivers;

use App\Models\OnlineConsultation;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Contracts\PaymentInitResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StripeGateway implements PaymentGatewayInterface
{
    private string $secretKey;

    private string $webhookSecret;

    private bool $testMode;

    public function __construct()
    {
        $this->secretKey = (string) Setting::get('stripe_secret_key', '');
        $this->webhookSecret = (string) Setting::get('stripe_webhook_secret', '');
        $this->testMode = (bool) Setting::get('stripe_test_mode', true);
    }

    public function getCode(): string
    {
        return 'stripe';
    }

    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function isEnabled(): bool
    {
        return (bool) Setting::get('stripe_enabled', false) && ! empty($this->secretKey);
    }

    public function initiatePayment(OnlineConsultation $consultation): PaymentInitResult
    {
        try {
            $amountCents = (int) round($consultation->fee * 100);

            $response = Http::asForm()
                ->withHeaders(['Authorization' => 'Bearer '.$this->secretKey])
                ->timeout(15)
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'payment_method_types[]' => 'card',
                    'line_items[0][price_data][currency]' => 'egp',
                    'line_items[0][price_data][product_data][name]' => 'Online Consultation '.$consultation->consultation_number,
                    'line_items[0][price_data][unit_amount]' => $amountCents,
                    'line_items[0][quantity]' => 1,
                    'success_url' => url('/patient/online-consultation/success?id='.$consultation->id),
                    'cancel_url' => url('/patient/online-consultation/cancelled?id='.$consultation->id),
                    'client_reference_id' => (string) $consultation->id,
                    'metadata[consultation_id]' => (string) $consultation->id,
                ]);

            if (! $response->successful()) {
                return PaymentInitResult::failure('Stripe error: '.$response->body());
            }

            $session = $response->json();

            $transaction = PaymentTransaction::create([
                'gateway' => 'stripe',
                'intent_id' => $session['id'],
                'online_consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'amount' => $consultation->fee,
                'currency' => 'EGP',
                'status' => 'pending',
                'type' => 'payment',
                'checkout_url' => $session['url'],
                'gateway_request' => $session,
            ]);

            return PaymentInitResult::success($session['url'], $session['id'], $transaction->transaction_id);
        } catch (\Throwable $e) {
            report($e);

            return PaymentInitResult::failure('Stripe error: '.$e->getMessage());
        }
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        if (! $signature || empty($this->webhookSecret)) {
            return false;
        }

        // Parse signature header
        $parts = [];
        foreach (explode(',', $signature) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $expectedSig = $parts['v1'] ?? '';
        if (! $timestamp || ! $expectedSig) {
            return false;
        }

        $payload = $timestamp.'.'.$request->getContent();
        $computedSig = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSig, $computedSig);
    }

    public function handleWebhook(Request $request): ?PaymentTransaction
    {
        $event = $request->json()->all();
        if (($event['type'] ?? '') !== 'checkout.session.completed') {
            return null;
        }

        $session = $event['data']['object'] ?? [];
        $sessionId = $session['id'] ?? '';
        if (! $sessionId) {
            return null;
        }

        $transaction = PaymentTransaction::where('gateway', 'stripe')
            ->where('intent_id', $sessionId)
            ->first();

        if (! $transaction) {
            return null;
        }

        $transaction->gateway_response = $session;
        $transaction->gateway_reference = $session['payment_intent'] ?? '';
        $transaction->status = 'succeeded';
        $transaction->paid_at = now();
        $transaction->save();

        return $transaction;
    }

    public function refund(PaymentTransaction $transaction, ?float $amount = null): bool
    {
        // Only a succeeded charge with a Stripe payment-intent reference can be refunded.
        if ($transaction->status !== 'succeeded' || empty($transaction->gateway_reference)) {
            return false;
        }

        try {
            $params = ['payment_intent' => $transaction->gateway_reference];
            // Omitting amount = full refund; otherwise partial (in cents).
            if ($amount !== null) {
                $params['amount'] = (int) round($amount * 100);
            }

            $response = Http::asForm()
                ->withHeaders(['Authorization' => 'Bearer '.$this->secretKey])
                ->timeout(15)
                ->post('https://api.stripe.com/v1/refunds', $params);

            if (! $response->successful()) {
                $transaction->failure_reason = 'Refund failed: '.$response->body();
                $transaction->save();

                return false;
            }

            $transaction->status = 'refunded';
            $transaction->gateway_response = array_merge((array) $transaction->gateway_response, ['refund' => $response->json()]);
            $transaction->save();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function testConnection(): array
    {
        if (empty($this->secretKey)) {
            return ['success' => false, 'message' => 'Stripe secret key not configured'];
        }
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer '.$this->secretKey])
                ->timeout(10)
                ->get('https://api.stripe.com/v1/balance');
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Stripe connection OK (mode: '.($this->testMode ? 'TEST' : 'LIVE').')'];
            }

            return ['success' => false, 'message' => 'Stripe error: '.$response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Stripe error: '.$e->getMessage()];
        }
    }
}

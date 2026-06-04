<?php

namespace App\Services\Payment\Drivers;

use App\Models\OnlineConsultation;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Contracts\PaymentInitResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymobGateway implements PaymentGatewayInterface
{
    private string $apiKey;

    private string $hmacSecret;

    private string $iframeId;

    private string $integrationId;

    private bool $testMode;

    private string $baseUrl = 'https://accept.paymob.com/api';

    public function __construct()
    {
        $this->apiKey = (string) Setting::get('paymob_api_key', '');
        $this->hmacSecret = (string) Setting::get('paymob_hmac_secret', '');
        $this->iframeId = (string) Setting::get('paymob_iframe_id', '');
        $this->integrationId = (string) Setting::get('paymob_integration_id', '');
        $this->testMode = (bool) Setting::get('paymob_test_mode', true);
    }

    public function getCode(): string
    {
        return 'paymob';
    }

    public function getDisplayName(): string
    {
        return 'Paymob';
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function isEnabled(): bool
    {
        return (bool) Setting::get('paymob_enabled', false)
            && ! empty($this->apiKey)
            && ! empty($this->integrationId);
    }

    public function initiatePayment(OnlineConsultation $consultation): PaymentInitResult
    {
        try {
            // Step 1: Auth
            $authResponse = Http::timeout(15)->post("{$this->baseUrl}/auth/tokens", [
                'api_key' => $this->apiKey,
            ]);
            if (! $authResponse->successful()) {
                return PaymentInitResult::failure('Paymob auth failed: '.$authResponse->body());
            }
            $authToken = $authResponse->json('token');

            // Step 2: Create order
            $merchantOrderId = 'TELE-'.$consultation->id.'-'.time();
            $amountCents = (int) round($consultation->fee * 100);

            $orderResponse = Http::timeout(15)->post("{$this->baseUrl}/ecommerce/orders", [
                'auth_token' => $authToken,
                'delivery_needed' => 'false',
                'amount_cents' => $amountCents,
                'currency' => 'EGP',
                'merchant_order_id' => $merchantOrderId,
                'items' => [[
                    'name' => 'Online Consultation '.$consultation->consultation_number,
                    'amount_cents' => $amountCents,
                    'quantity' => 1,
                ]],
            ]);
            if (! $orderResponse->successful()) {
                return PaymentInitResult::failure('Paymob order failed: '.$orderResponse->body());
            }
            $orderId = $orderResponse->json('id');

            // Step 3: Payment key
            $patient = $consultation->patient;
            $paymentKeyResponse = Http::timeout(15)->post("{$this->baseUrl}/acceptance/payment_keys", [
                'auth_token' => $authToken,
                'amount_cents' => $amountCents,
                'expiration' => 3600,
                'order_id' => $orderId,
                'billing_data' => [
                    'first_name' => explode(' ', $patient->full_name)[0] ?? 'Patient',
                    'last_name' => explode(' ', $patient->full_name, 2)[1] ?? 'User',
                    'email' => $patient->email ?: 'patient-'.$patient->id.'@doctorato.net',
                    'phone_number' => $patient->phone ?: '+201000000000',
                    'country' => 'EG',
                    'city' => $patient->address ?: 'Cairo',
                    'street' => 'NA',
                    'building' => 'NA',
                    'floor' => 'NA',
                    'apartment' => 'NA',
                ],
                'currency' => 'EGP',
                'integration_id' => (int) $this->integrationId,
            ]);
            if (! $paymentKeyResponse->successful()) {
                return PaymentInitResult::failure('Paymob payment key failed: '.$paymentKeyResponse->body());
            }
            $paymentToken = $paymentKeyResponse->json('token');

            $checkoutUrl = "https://accept.paymob.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentToken}";

            // Store transaction
            $transaction = PaymentTransaction::create([
                'gateway' => 'paymob',
                'intent_id' => (string) $orderId,
                'online_consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'amount' => $consultation->fee,
                'currency' => 'EGP',
                'status' => 'pending',
                'type' => 'payment',
                'checkout_url' => $checkoutUrl,
                'gateway_request' => [
                    'merchant_order_id' => $merchantOrderId,
                    'order_id' => $orderId,
                ],
            ]);

            return PaymentInitResult::success(
                $checkoutUrl,
                (string) $orderId,
                $transaction->transaction_id,
                ['payment_token' => $paymentToken]
            );
        } catch (\Throwable $e) {
            report($e);

            return PaymentInitResult::failure('Paymob error: '.$e->getMessage());
        }
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        if (empty($this->hmacSecret)) {
            return false;
        }

        $data = $request->input('obj', []);
        if (empty($data)) {
            return false;
        }

        // Paymob HMAC order (from their docs)
        $hmacFields = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order.id', 'owner', 'pending',
            'source_data.pan', 'source_data.sub_type', 'source_data.type',
            'success',
        ];

        $concat = '';
        foreach ($hmacFields as $field) {
            $value = data_get($data, $field, '');
            $concat .= is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        $expectedHmac = hash_hmac('sha512', $concat, $this->hmacSecret);

        return hash_equals($expectedHmac, (string) $request->input('hmac', ''));
    }

    public function handleWebhook(Request $request): ?PaymentTransaction
    {
        $data = $request->input('obj', []);
        $orderId = (string) data_get($data, 'order.id', '');
        if (empty($orderId)) {
            return null;
        }

        $transaction = PaymentTransaction::where('gateway', 'paymob')
            ->where('intent_id', $orderId)
            ->first();

        if (! $transaction) {
            return null;
        }

        $success = (bool) data_get($data, 'success', false);
        $transaction->gateway_response = $data;
        $transaction->gateway_reference = (string) data_get($data, 'id', '');
        $transaction->status = $success ? 'succeeded' : 'failed';
        if ($success) {
            $transaction->paid_at = now();
        } else {
            $transaction->failed_at = now();
            $transaction->failure_reason = (string) data_get($data, 'data.message', 'Payment failed');
        }
        $transaction->save();

        return $transaction;
    }

    public function refund(PaymentTransaction $transaction, ?float $amount = null): bool
    {
        // Only a succeeded charge with a Paymob transaction reference can be refunded.
        if ($transaction->status !== 'succeeded' || empty($transaction->gateway_reference)) {
            return false;
        }

        try {
            // Step 1: Auth to obtain a fresh token.
            $auth = Http::timeout(15)->post("{$this->baseUrl}/auth/tokens", ['api_key' => $this->apiKey]);
            if (! $auth->successful() || empty($auth->json('token'))) {
                return false;
            }

            // Step 2: Refund (full amount unless a partial amount is given).
            $cents = (int) round(($amount ?? (float) $transaction->amount) * 100);
            $response = Http::timeout(20)->post("{$this->baseUrl}/acceptance/void_refund/refund", [
                'auth_token' => $auth->json('token'),
                'transaction_id' => $transaction->gateway_reference,
                'amount_cents' => $cents,
            ]);

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
        if (! $this->isEnabled()) {
            return ['success' => false, 'message' => 'Paymob is not enabled or missing credentials'];
        }
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/auth/tokens", ['api_key' => $this->apiKey]);
            if ($response->successful() && ! empty($response->json('token'))) {
                return ['success' => true, 'message' => 'Paymob auth OK (test mode: '.($this->testMode ? 'ON' : 'OFF').')'];
            }

            return ['success' => false, 'message' => 'Paymob auth failed: '.$response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Paymob error: '.$e->getMessage()];
        }
    }
}

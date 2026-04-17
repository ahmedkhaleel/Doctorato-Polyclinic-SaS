<?php

namespace App\Services\Payment\Contracts;

use App\Models\OnlineConsultation;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function getCode(): string;  // 'paymob' or 'stripe'
    public function getDisplayName(): string;
    public function isEnabled(): bool;
    public function isTestMode(): bool;

    public function initiatePayment(OnlineConsultation $consultation): PaymentInitResult;
    public function handleWebhook(Request $request): ?PaymentTransaction;
    public function verifyWebhookSignature(Request $request): bool;
    public function refund(PaymentTransaction $transaction, ?float $amount = null): bool;
    public function testConnection(): array;
}

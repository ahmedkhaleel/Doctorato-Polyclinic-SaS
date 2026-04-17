<?php

namespace App\Services\Payment\Contracts;

class PaymentInitResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $checkoutUrl = null,
        public readonly ?string $intentId = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $metadata = [],
    ) {
    }

    public static function success(string $checkoutUrl, string $intentId, string $transactionId, array $metadata = []): self
    {
        return new self(true, $checkoutUrl, $intentId, $transactionId, null, $metadata);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, null, null, null, $errorMessage);
    }
}

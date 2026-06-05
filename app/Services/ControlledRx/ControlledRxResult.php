<?php

namespace App\Services\ControlledRx;

/** Outcome of submitting a controlled prescription to a (national) gateway. */
class ControlledRxResult
{
    public function __construct(
        public bool $ok,
        public string $status,          // authorized | pending | failed
        public ?string $externalRef = null,
        public ?string $message = null,
        public array $raw = [],
    ) {}

    public static function authorized(string $ref, array $raw = []): self
    {
        return new self(true, 'authorized', $ref, null, $raw);
    }

    public static function pending(?string $message = null, array $raw = []): self
    {
        return new self(true, 'pending', null, $message, $raw);
    }

    public static function failed(string $message): self
    {
        return new self(false, 'failed', null, $message);
    }
}

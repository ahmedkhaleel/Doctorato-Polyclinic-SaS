<?php

namespace App\Services\Notifications;

/** Result of a single channel-driver send attempt. */
class DeliveryResult
{
    public function __construct(
        public bool $success,
        public string $provider = '',
        public ?float $cost = null,
        public ?string $providerRef = null,
        public ?string $error = null,
    ) {}

    public static function ok(string $provider = '', ?float $cost = null, ?string $ref = null): self
    {
        return new self(true, $provider, $cost, $ref);
    }

    public static function fail(string $error, string $provider = ''): self
    {
        return new self(false, $provider, null, null, $error);
    }
}

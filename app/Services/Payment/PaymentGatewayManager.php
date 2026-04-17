<?php

namespace App\Services\Payment;

use App\Models\Setting;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Drivers\PaymobGateway;
use App\Services\Payment\Drivers\StripeGateway;

class PaymentGatewayManager
{
    public function all(): array
    {
        return [
            'paymob' => app(PaymobGateway::class),
            'stripe' => app(StripeGateway::class),
        ];
    }

    public function get(string $code): ?PaymentGatewayInterface
    {
        return $this->all()[$code] ?? null;
    }

    public function getActive(): ?PaymentGatewayInterface
    {
        $code = (string) Setting::get('payment_active_gateway', 'paymob');
        $gateway = $this->get($code);
        return ($gateway && $gateway->isEnabled()) ? $gateway : null;
    }

    public function getEnabled(): array
    {
        return array_filter($this->all(), fn ($g) => $g->isEnabled());
    }
}

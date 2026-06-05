<?php

namespace App\Services\ControlledRx;

use App\Models\Setting;
use App\Services\ControlledRx\Contracts\ControlledRxGatewayInterface;
use App\Services\ControlledRx\Drivers\EgyptControlledRx;
use App\Services\ControlledRx\Drivers\InternalControlledRx;
use App\Services\ControlledRx\Drivers\KsaControlledRx;
use App\Services\ControlledRx\Drivers\UaeControlledRx;

/** Resolves the active controlled-Rx gateway (setting controlled_rx_gateway; default internal). */
class ControlledRxManager
{
    /** @return array<string,ControlledRxGatewayInterface> */
    public function all(): array
    {
        return [
            'internal' => new InternalControlledRx,
            'ksa' => new KsaControlledRx,
            'uae' => new UaeControlledRx,
            'egypt' => new EgyptControlledRx,
        ];
    }

    public function get(string $code): ?ControlledRxGatewayInterface
    {
        return $this->all()[$code] ?? null;
    }

    public function active(): ControlledRxGatewayInterface
    {
        $code = (string) Setting::get('controlled_rx_gateway', 'internal');
        $gateway = $this->get($code);

        // Fall back to the always-available internal register if the configured
        // national driver isn't set up yet.
        return ($gateway && $gateway->isConfigured()) ? $gateway : new InternalControlledRx;
    }
}

<?php

namespace App\Services\ControlledRx\Drivers;

use App\Models\ControlledPrescription;
use App\Models\Setting;
use App\Services\ControlledRx\Contracts\ControlledRxGatewayInterface;
use App\Services\ControlledRx\ControlledRxResult;

/**
 * UAE national controlled-substance platform driver (STUB). The regulator
 * API spec + credentials are released only to licensed facilities (ADR §8.2),
 * so this remains a stub: not configured → submissions fail gracefully with a
 * clear message. Wire the real HTTP calls once credentials are provisioned.
 */
class UaeControlledRx implements ControlledRxGatewayInterface
{
    public function code(): string
    {
        return 'uae';
    }

    public function isConfigured(): bool
    {
        return ! empty(Setting::get('controlled_rx_uae_api_key', ''));
    }

    public function submit(ControlledPrescription $rx): ControlledRxResult
    {
        if (! $this->isConfigured()) {
            return ControlledRxResult::failed('UAE controlled-Rx platform not configured (facility credentials required).');
        }

        // TODO: implement the live UAE regulator API call once credentials are available.
        return ControlledRxResult::failed('UAE controlled-Rx integration not yet implemented.');
    }

    public function patientHistory(int $patientId): array
    {
        return [];
    }
}

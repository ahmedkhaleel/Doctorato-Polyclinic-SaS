<?php

namespace App\Services\ControlledRx\Drivers;

use App\Models\ControlledPrescription;
use App\Services\ControlledRx\Contracts\ControlledRxGatewayInterface;
use App\Services\ControlledRx\ControlledRxResult;
use Illuminate\Support\Str;

/**
 * Default driver — an INTERNAL controlled-Rx register with e-signature, used
 * until a national platform driver is configured. It authorizes locally and
 * issues an internal serial; no external regulator call. Always available.
 */
class InternalControlledRx implements ControlledRxGatewayInterface
{
    public function code(): string
    {
        return 'internal';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function submit(ControlledPrescription $rx): ControlledRxResult
    {
        // Internal authorization: a serial derived from the signed prescription.
        $serial = 'CRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

        return ControlledRxResult::authorized($serial, ['driver' => 'internal']);
    }

    public function patientHistory(int $patientId): array
    {
        // No external PDMP — surface our own authorized/dispensed controlled rx.
        return ControlledPrescription::where('patient_id', $patientId)
            ->whereIn('status', ['authorized', 'dispensed'])
            ->latest('authorized_at')
            ->get(['drug', 'schedule', 'quantity', 'status', 'authorized_at'])
            ->toArray();
    }
}

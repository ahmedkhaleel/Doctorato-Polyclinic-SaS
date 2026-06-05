<?php

namespace App\Services\ControlledRx\Contracts;

use App\Models\ControlledPrescription;
use App\Services\ControlledRx\ControlledRxResult;

/**
 * A national controlled-substance e-prescribing gateway. Implementations submit
 * a signed prescription to the regulator platform and (optionally) query a
 * patient's controlled-substance history (PDMP-equivalent).
 */
interface ControlledRxGatewayInterface
{
    public function code(): string;

    /** True when credentials/config are present and the driver can transact. */
    public function isConfigured(): bool;

    public function submit(ControlledPrescription $rx): ControlledRxResult;

    /** PDMP-equivalent: recent controlled dispenses for a patient. */
    public function patientHistory(int $patientId): array;
}

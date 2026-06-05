<?php

namespace App\Services\ControlledRx;

use App\Models\ControlledPrescription;
use Illuminate\Validation\ValidationException;

/**
 * Controlled-substance e-prescribing workflow (layer B):
 *   draft → sign (requires strong-auth/MFA) → submit (via gateway) → authorized
 *   → dispensed. Signing computes a non-repudiation hash; submission can only
 *   happen on a signed prescription. MFA + PDMP are pluggable hooks.
 */
class ControlledRxService
{
    public function __construct(private ControlledRxManager $gateways) {}

    /** Strong-auth hook. Real verifier (TOTP/national identity) plugged later. */
    public function verifyMfa(?string $token): bool
    {
        // Layer B: a non-empty token is accepted as a placeholder for the real
        // second factor. Wire the actual verifier when a provider is chosen.
        return ! empty($token);
    }

    /** Sign a draft prescription (requires MFA). Stamps a non-repudiation hash. */
    public function sign(ControlledPrescription $rx, int $doctorId, ?string $mfaToken): ControlledPrescription
    {
        if ($rx->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft prescription can be signed.']);
        }
        if (! $this->verifyMfa($mfaToken)) {
            throw ValidationException::withMessages(['mfa_token' => 'Strong authentication (second factor) is required to sign a controlled prescription.']);
        }

        $payload = implode('|', [$rx->id, $rx->patient_id, $doctorId, $rx->drug, $rx->dose, $rx->quantity, now()->toIso8601String()]);
        $rx->update([
            'status' => 'signed',
            'signature_hash' => hash('sha256', $payload),
            'signed_by' => 'doctor',
            'signed_at' => now(),
        ]);

        return $rx->fresh();
    }

    /** Submit a signed prescription to the active gateway. */
    public function submit(ControlledPrescription $rx): ControlledPrescription
    {
        if (! $rx->isSigned()) {
            throw ValidationException::withMessages(['signature' => 'A controlled prescription must be signed before submission.']);
        }

        $gateway = $this->gateways->active();
        $rx->update(['status' => 'submitted', 'submitted_at' => now(), 'gateway' => $gateway->code()]);

        $result = $gateway->submit($rx);

        if ($result->ok && $result->status === 'authorized') {
            $rx->update([
                'status' => 'authorized',
                'authorized_at' => now(),
                'external_ref' => $result->externalRef,
                'gateway_response' => $result->raw,
            ]);
        } elseif (! $result->ok) {
            $rx->update(['status' => 'signed', 'gateway_response' => ['error' => $result->message]]);
            throw ValidationException::withMessages(['gateway' => $result->message ?? 'Submission failed.']);
        }

        return $rx->fresh();
    }

    public function markDispensed(ControlledPrescription $rx): ControlledPrescription
    {
        if ($rx->status !== 'authorized') {
            throw ValidationException::withMessages(['status' => 'Only an authorized prescription can be dispensed.']);
        }
        $rx->update(['status' => 'dispensed', 'dispensed_at' => now()]);

        return $rx->fresh();
    }

    /** PDMP-equivalent history via the active gateway. */
    public function patientHistory(int $patientId): array
    {
        return $this->gateways->active()->patientHistory($patientId);
    }
}

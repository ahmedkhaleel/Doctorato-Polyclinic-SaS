<?php

namespace App\Services\Pricing;

use App\Models\Doctor;
use App\Models\Setting;
use App\Services\ModuleManager;

/**
 * Single source of truth for a medical module's consultation pricing —
 * consultant fee, specialist fee, base consultation fee, follow-up fee, and
 * follow-up window — across booking, billing and commission.
 *
 * Storage is currently split (legacy global Settings for derma/dental/
 * pediatric; module_settings for obgyn/psychiatry/neurology). This resolver
 * hides that: callers ask by module + doctor_type and get one answer.
 */
class PricingResolver
{
    /**
     * ADR-001 Phase 2 (fallback-protected READ flip): module_settings is now the
     * primary store for ALL modules (canonical keys below). The four legacy
     * modules also carry a `legacy` map of their old global Setting keys —
     * feesFor() reads module_settings first and falls back to the legacy Setting
     * when a key is ABSENT. This is SAFE whether or not the Phase-1 backfill has
     * run in a given environment (no zero-pricing risk) and is fully reversible.
     * Phase 4 drops the legacy maps once backfill + write-path are confirmed.
     */
    private function source(string $module): array
    {
        $moduleKeys = [
            'consultant' => 'consultant_fee',
            'specialist' => 'specialist_fee',
            'base' => 'consultation_fee',
            'followup' => 'followup_fee',
            'window' => 'followup_window_days',
        ];

        $legacy = match ($module) {
            'derma', 'dermatology' => [
                'consultant' => 'dermatology_consultant_fee',
                'specialist' => 'dermatology_specialist_fee',
                'base' => 'default_dermatology_fee',
                'followup' => 'followup_fee',
                'window' => 'followup_window_days',
            ],
            'cosmetic' => [
                'consultant' => 'cosmetic_consultation_fee',
                'specialist' => 'cosmetic_consultation_fee',
                'base' => 'cosmetic_consultation_fee',
                'followup' => 'followup_fee',
                'window' => 'followup_window_days',
            ],
            'dental' => [
                'consultant' => 'dental_consultant_fee',
                'specialist' => 'dental_specialist_fee',
                'base' => 'dental_consultant_fee',
                'followup' => 'followup_fee',
                'window' => 'followup_window_days',
            ],
            'pediatric' => [
                'consultant' => 'pediatric_consultant_fee',
                'specialist' => 'pediatric_specialist_fee',
                'base' => 'pediatric_consultant_fee',
                'followup' => 'pediatric_followup_fee',
                'window' => 'followup_window_days',
            ],
            default => null, // obgyn / psychiatry / neurology: module_settings only
        };

        return ['module_keys' => $moduleKeys, 'legacy' => $legacy];
    }

    /** Doctor-level per-specialty fee override column (highest priority). */
    private function doctorOverrideColumn(string $module): ?string
    {
        return match ($module) {
            'derma', 'dermatology' => 'dermatology_fee',
            'cosmetic' => 'cosmetic_fee',
            'dental' => 'dental_consultation_fee',
            'pediatric' => 'pediatric_consultation_fee',
            'obgyn' => 'obgyn_consultation_fee',
            'psychiatry' => 'psychiatry_consultation_fee',
            'neurology' => 'neurology_consultation_fee',
            default => null,
        };
    }

    /**
     * All pricing values for a module: consultant / specialist / base /
     * followup fees + follow-up window (days). Drives both server-side
     * resolution and the booking-form fee props.
     */
    public function feesFor(string $module): array
    {
        $src = $this->source($module);
        $moduleKeys = $src['module_keys'];
        $legacy = $src['legacy'];

        // Read a field from module_settings first; only a POSITIVE value there
        // overrides. Otherwise fall back to the legacy global Setting (the 4
        // legacy modules only). Treating 0/empty as "not set" is deliberate: a
        // module_settings row pre-seeded to 0 must NEVER zero out a live fee —
        // it falls back to the legacy value until a real (positive) value is
        // backfilled/entered. Behaviour-identical to pre-Phase-2 in every env:
        //   • legacy module, module_settings 0/absent → legacy Setting (as before)
        //   • legacy module, backfilled (positive)    → same number (= legacy)
        //   • obgyn/psych/neuro                        → module_settings (as before)
        $read = function (string $field, bool $isWindow = false) use ($module, $moduleKeys, $legacy) {
            $raw = ModuleManager::getSetting($module, $moduleKeys[$field], null);
            $mv = $isWindow ? (int) $raw : (float) $raw;
            if ($mv > 0) {
                return $mv;
            }
            if ($legacy !== null) {
                $lv = Setting::get($legacy[$field], $isWindow ? 15 : 0);

                return $isWindow ? (int) $lv : (float) $lv;
            }

            return $isWindow ? 14 : 0.0;
        };

        return [
            'consultant' => $read('consultant'),
            'specialist' => $read('specialist'),
            'base' => $read('base'),
            'followup' => $read('followup'),
            'window' => $read('window', true),
        ];
    }

    /**
     * Resolve the consultation fee for a specific doctor.
     * Order: follow-up → doctor override → consultant/specialist → base.
     */
    public function consultationFee(Doctor $doctor, string $module, bool $isFollowUp = false): float
    {
        $fees = $this->feesFor($module);

        if ($isFollowUp && $fees['followup'] > 0) {
            return $fees['followup'];
        }

        if ($col = $this->doctorOverrideColumn($module)) {
            $override = (float) ($doctor->{$col} ?? 0);
            if ($override > 0) {
                return $override;
            }
        }

        if ($doctor->doctor_type === 'consultant' && $fees['consultant'] > 0) {
            return $fees['consultant'];
        }
        if ($doctor->doctor_type === 'specialist' && $fees['specialist'] > 0) {
            return $fees['specialist'];
        }

        return $fees['base'] > 0 ? $fees['base'] : ($fees['consultant'] ?: $fees['specialist']);
    }

    /** Follow-up window (days) for a module; 0 disables follow-ups. */
    public function followUpWindowDays(string $module): int
    {
        return $this->feesFor($module)['window'];
    }

    /** Follow-up fee for a module. */
    public function followUpFee(string $module): float
    {
        return $this->feesFor($module)['followup'];
    }
}

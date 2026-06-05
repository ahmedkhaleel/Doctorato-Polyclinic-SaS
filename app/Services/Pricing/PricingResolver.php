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
     * Where each module's consultant/specialist/base/followup fees + window
     * live. `settings` = global Setting keys; `module` = module_settings keys.
     */
    private function source(string $module): array
    {
        return match ($module) {
            'derma', 'dermatology' => [
                'driver' => 'settings',
                'consultant' => 'dermatology_consultant_fee',
                'specialist' => 'dermatology_specialist_fee',
                'base' => 'default_dermatology_fee',
                'followup' => 'followup_fee',
                'window' => 'followup_window_days',
            ],
            'cosmetic' => [
                'driver' => 'settings',
                'consultant' => 'cosmetic_consultation_fee',
                'specialist' => 'cosmetic_consultation_fee',
                'base' => 'cosmetic_consultation_fee',
                'followup' => 'followup_fee',
                'window' => 'followup_window_days',
            ],
            'dental' => [
                'driver' => 'settings',
                'consultant' => 'dental_consultant_fee',
                'specialist' => 'dental_specialist_fee',
                'base' => 'dental_consultant_fee',
                'followup' => 'followup_fee',
                'window' => 'followup_window_days',
            ],
            'pediatric' => [
                'driver' => 'settings',
                'consultant' => 'pediatric_consultant_fee',
                'specialist' => 'pediatric_specialist_fee',
                'base' => 'pediatric_consultant_fee',
                'followup' => 'pediatric_followup_fee',
                'window' => 'followup_window_days',
            ],
            // module_settings-backed specialties (obgyn / psychiatry / neurology)
            default => [
                'driver' => 'module',
                'consultant' => 'consultant_fee',
                'specialist' => 'specialist_fee',
                'base' => 'consultation_fee',
                'followup' => 'followup_fee',
                'window' => 'followup_window_days',
            ],
        };
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

        if ($src['driver'] === 'module') {
            return [
                'consultant' => (float) ModuleManager::getSetting($module, $src['consultant'], 0),
                'specialist' => (float) ModuleManager::getSetting($module, $src['specialist'], 0),
                'base' => (float) ModuleManager::getSetting($module, $src['base'], 0),
                'followup' => (float) ModuleManager::getSetting($module, $src['followup'], 0),
                'window' => (int) ModuleManager::getSetting($module, $src['window'], 14),
            ];
        }

        return [
            'consultant' => (float) Setting::get($src['consultant'], 0),
            'specialist' => (float) Setting::get($src['specialist'], 0),
            'base' => (float) Setting::get($src['base'], 0),
            'followup' => (float) Setting::get($src['followup'], 0),
            'window' => (int) Setting::get($src['window'], 15),
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

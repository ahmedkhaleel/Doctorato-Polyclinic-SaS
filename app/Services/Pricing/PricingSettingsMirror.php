<?php

namespace App\Services\Pricing;

use App\Models\Setting;
use App\Services\ModuleManager;
use Illuminate\Support\Facades\DB;

/**
 * ADR-001 Phase 1 + 3 — keeps `module_settings` consultation fees in sync with
 * the legacy global `Setting` fee keys for the four legacy modules
 * (derma / cosmetic / dental / pediatric).
 *
 *  - Phase 1 (backfill command): mirror() copies current legacy values across.
 *  - Phase 3 (write path): the settings editors call mirror() after saving, so
 *    a fee edited in the admin UI lands in BOTH stores (dual-write). The
 *    PricingResolver already prefers a positive module_settings value, so the
 *    mirrored value takes effect immediately while the legacy key stays written
 *    as a rollback path until Phase 4 retires it.
 *
 * Idempotent (upsert). `ModuleManager::setSetting` is UPDATE-only and cannot be
 * used here (legacy modules have no fee rows yet), hence the direct upsert.
 */
class PricingSettingsMirror
{
    /** module → [module_settings key => legacy Setting key]. Mirrors PricingResolver::source() legacy maps. */
    public const MAP = [
        'derma' => [
            'consultant_fee' => 'dermatology_consultant_fee',
            'specialist_fee' => 'dermatology_specialist_fee',
            'consultation_fee' => 'default_dermatology_fee',
            'followup_fee' => 'followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
        'cosmetic' => [
            'consultant_fee' => 'cosmetic_consultation_fee',
            'specialist_fee' => 'cosmetic_consultation_fee',
            'consultation_fee' => 'cosmetic_consultation_fee',
            'followup_fee' => 'followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
        'dental' => [
            'consultant_fee' => 'dental_consultant_fee',
            'specialist_fee' => 'dental_specialist_fee',
            'consultation_fee' => 'dental_consultant_fee',
            'followup_fee' => 'followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
        'pediatric' => [
            // Single consultation fee (the pediatric editor's key) drives all three.
            'consultant_fee' => 'pediatric_consultation_fee',
            'specialist_fee' => 'pediatric_consultation_fee',
            'consultation_fee' => 'pediatric_consultation_fee',
            'followup_fee' => 'pediatric_followup_fee',
            'followup_window_days' => 'followup_window_days',
        ],
    ];

    /** The legacy Setting keys this mirror cares about (for change detection). */
    public static function legacyKeys(): array
    {
        $keys = [];
        foreach (self::MAP as $map) {
            foreach ($map as $legacy) {
                $keys[$legacy] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * Re-read every legacy fee Setting and upsert it into module_settings.
     * Returns the rows written as [module, key, fromLegacyKey, value].
     *
     * @param  bool  $dryRun  when true, computes rows but writes nothing.
     */
    public function mirror(bool $dryRun = false): array
    {
        $rows = [];

        foreach (self::MAP as $module => $keys) {
            foreach ($keys as $targetKey => $legacyKey) {
                $default = $targetKey === 'followup_window_days' ? 15 : 0;
                $value = Setting::get($legacyKey, $default);
                $rows[] = [$module, $targetKey, $legacyKey, (string) $value];

                if (! $dryRun) {
                    DB::table('module_settings')->updateOrInsert(
                        ['module' => $module, 'key' => $targetKey],
                        [
                            'value' => (string) $value,
                            'type' => $targetKey === 'followup_window_days' ? 'number' : 'price',
                            'group' => 'pricing',
                            'updated_at' => now(),
                        ],
                    );
                }
            }
        }

        if (! $dryRun) {
            ModuleManager::clearCache();
        }

        return $rows;
    }

    /** True if any of the saved keys is a legacy fee key this mirror tracks. */
    public function touchesPricing(array $savedKeys): bool
    {
        return count(array_intersect($savedKeys, self::legacyKeys())) > 0;
    }
}

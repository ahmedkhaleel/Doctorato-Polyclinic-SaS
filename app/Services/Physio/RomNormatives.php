<?php

namespace App\Services\Physio;

/**
 * Normal active range-of-motion values (degrees), AAOS reference. Used to
 * pre-fill the `normal_ref` column and to draw the TrendLine normal corridor.
 */
class RomNormatives
{
    public const NORMS = [
        'shoulder' => ['flexion' => 180, 'extension' => 60, 'abduction' => 180, 'adduction' => 50, 'internal_rotation' => 70, 'external_rotation' => 90],
        'elbow' => ['flexion' => 150, 'extension' => 0, 'pronation' => 80, 'supination' => 80],
        'wrist' => ['flexion' => 80, 'extension' => 70, 'radial_deviation' => 20, 'ulnar_deviation' => 30],
        'hip' => ['flexion' => 120, 'extension' => 30, 'abduction' => 45, 'adduction' => 30, 'internal_rotation' => 45, 'external_rotation' => 45],
        'knee' => ['flexion' => 135, 'extension' => 0],
        'ankle' => ['dorsiflexion' => 20, 'plantarflexion' => 50, 'inversion' => 35, 'eversion' => 15],
        'cervical' => ['flexion' => 50, 'extension' => 60, 'lateral_flexion' => 45, 'rotation' => 80],
        'lumbar' => ['flexion' => 60, 'extension' => 25, 'lateral_flexion' => 25, 'rotation' => 30],
    ];

    public static function for(string $joint, string $motion): ?float
    {
        $j = strtolower(trim($joint));
        $m = strtolower(trim($motion));

        return isset(self::NORMS[$j][$m]) ? (float) self::NORMS[$j][$m] : null;
    }

    public static function joints(): array
    {
        return array_keys(self::NORMS);
    }
}

<?php

namespace App\Services;

/**
 * WHO Child Growth Standards — LMS-based z-score & percentile calculator.
 *
 * Uses the simplified LMS (Lambda-Mu-Sigma) reference data for:
 *   • Weight-for-age (0–120 months, boys & girls)
 *   • Length/Height-for-age (0–228 months)
 *   • Head circumference-for-age (0–60 months)
 *   • BMI-for-age (0–228 months)
 *
 * Reference: WHO Multicentre Growth Reference Study Group (2006/2007).
 * Data points are a curated subset at key month intervals.
 */
class WhoGrowthStandardsService
{
    /**
     * Calculate z-score from an observed value using LMS parameters.
     *
     * Formula (Box-Cox):
     *   Z = ((value / M)^L − 1) / (L × S)   when L ≠ 0
     *   Z = ln(value / M) / S                 when L = 0
     */
    public static function zScore(float $value, float $L, float $M, float $S): float
    {
        if ($M <= 0 || $S <= 0) return 0;
        if (abs($L) < 0.001) {
            return log($value / $M) / $S;
        }
        return (pow($value / $M, $L) - 1) / ($L * $S);
    }

    /**
     * Convert z-score to percentile using the standard normal CDF approximation.
     */
    public static function zToPercentile(float $z): float
    {
        // Abramowitz & Stegun approximation (error < 1.5e-7)
        $t = 1.0 / (1.0 + 0.2316419 * abs($z));
        $d = 0.3989422804014327; // 1/sqrt(2*pi)
        $p = $d * exp(-$z * $z / 2.0) *
            ($t * (0.319381530 + $t * (-0.356563782 + $t * (1.781477937 + $t * (-1.821255978 + $t * 1.330274429)))));

        $percentile = $z > 0 ? (1.0 - $p) * 100 : $p * 100;
        return round(max(0.01, min(99.99, $percentile)), 2);
    }

    /**
     * Get weight-for-age z-score and percentile.
     */
    public static function weightForAge(float $weightKg, float $ageMonths, string $gender): array
    {
        $lms = self::interpolateLms(self::WEIGHT_FOR_AGE[$gender] ?? [], $ageMonths);
        if (!$lms) return ['zscore' => null, 'percentile' => null];

        $z = self::zScore($weightKg, $lms['L'], $lms['M'], $lms['S']);
        return [
            'zscore' => round($z, 2),
            'percentile' => self::zToPercentile($z),
        ];
    }

    /**
     * Get height-for-age z-score and percentile.
     */
    public static function heightForAge(float $heightCm, float $ageMonths, string $gender): array
    {
        $lms = self::interpolateLms(self::HEIGHT_FOR_AGE[$gender] ?? [], $ageMonths);
        if (!$lms) return ['zscore' => null, 'percentile' => null];

        $z = self::zScore($heightCm, $lms['L'], $lms['M'], $lms['S']);
        return [
            'zscore' => round($z, 2),
            'percentile' => self::zToPercentile($z),
        ];
    }

    /**
     * Get head-circumference-for-age z-score and percentile.
     */
    public static function headForAge(float $headCm, float $ageMonths, string $gender): array
    {
        $lms = self::interpolateLms(self::HEAD_FOR_AGE[$gender] ?? [], $ageMonths);
        if (!$lms) return ['zscore' => null, 'percentile' => null];

        $z = self::zScore($headCm, $lms['L'], $lms['M'], $lms['S']);
        return [
            'zscore' => round($z, 2),
            'percentile' => self::zToPercentile($z),
        ];
    }

    /**
     * Get BMI-for-age z-score and percentile.
     */
    public static function bmiForAge(float $bmi, float $ageMonths, string $gender): array
    {
        $lms = self::interpolateLms(self::BMI_FOR_AGE[$gender] ?? [], $ageMonths);
        if (!$lms) return ['zscore' => null, 'percentile' => null];

        $z = self::zScore($bmi, $lms['L'], $lms['M'], $lms['S']);
        return [
            'zscore' => round($z, 2),
            'percentile' => self::zToPercentile($z),
        ];
    }

    /**
     * Calculate ALL growth indicators at once for a patient measurement.
     */
    public static function calculateAll(
        float $ageMonths,
        string $gender,
        ?float $weightKg = null,
        ?float $heightCm = null,
        ?float $headCm = null,
        ?float $bmi = null
    ): array {
        $result = [
            'weight_zscore' => null, 'weight_percentile' => null,
            'height_zscore' => null, 'height_percentile' => null,
            'head_zscore' => null,   'head_percentile' => null,
            'bmi_zscore' => null,    'bmi_percentile' => null,
        ];

        $g = strtolower($gender) === 'female' ? 'female' : 'male';

        if ($weightKg && $weightKg > 0) {
            $w = self::weightForAge($weightKg, $ageMonths, $g);
            $result['weight_zscore'] = $w['zscore'];
            $result['weight_percentile'] = $w['percentile'];
        }

        if ($heightCm && $heightCm > 0) {
            $h = self::heightForAge($heightCm, $ageMonths, $g);
            $result['height_zscore'] = $h['zscore'];
            $result['height_percentile'] = $h['percentile'];
        }

        if ($headCm && $headCm > 0 && $ageMonths <= 60) {
            $hc = self::headForAge($headCm, $ageMonths, $g);
            $result['head_zscore'] = $hc['zscore'];
            $result['head_percentile'] = $hc['percentile'];
        }

        if ($bmi && $bmi > 0 && $ageMonths >= 0) {
            $b = self::bmiForAge($bmi, $ageMonths, $g);
            $result['bmi_zscore'] = $b['zscore'];
            $result['bmi_percentile'] = $b['percentile'];
        }

        return $result;
    }

    /**
     * Interpolate LMS values between two reference points.
     */
    private static function interpolateLms(array $table, float $ageMonths): ?array
    {
        if (empty($table)) return null;

        // Find bracketing entries
        $lower = null;
        $upper = null;

        foreach ($table as $entry) {
            if ($entry['month'] <= $ageMonths) {
                $lower = $entry;
            }
            if ($entry['month'] >= $ageMonths && $upper === null) {
                $upper = $entry;
            }
        }

        if (!$lower && !$upper) return null;
        if (!$lower) return $upper;
        if (!$upper) return $lower;
        if ($lower['month'] === $upper['month']) return $lower;

        // Linear interpolation
        $fraction = ($ageMonths - $lower['month']) / ($upper['month'] - $lower['month']);
        return [
            'L' => $lower['L'] + $fraction * ($upper['L'] - $lower['L']),
            'M' => $lower['M'] + $fraction * ($upper['M'] - $lower['M']),
            'S' => $lower['S'] + $fraction * ($upper['S'] - $lower['S']),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // WHO LMS Reference Data (curated key points)
    // Source: WHO Child Growth Standards (2006) & WHO Reference 2007
    // ═══════════════════════════════════════════════════════════

    const WEIGHT_FOR_AGE = [
        'male' => [
            ['month' => 0,  'L' => 0.3487, 'M' => 3.3464, 'S' => 0.14602],
            ['month' => 1,  'L' => 0.2297, 'M' => 4.4709, 'S' => 0.13395],
            ['month' => 2,  'L' => 0.1970, 'M' => 5.5675, 'S' => 0.12385],
            ['month' => 3,  'L' => 0.1738, 'M' => 6.3762, 'S' => 0.11727],
            ['month' => 4,  'L' => 0.1553, 'M' => 7.0023, 'S' => 0.11316],
            ['month' => 6,  'L' => 0.1276, 'M' => 7.9340, 'S' => 0.10862],
            ['month' => 9,  'L' => 0.0978, 'M' => 8.9014, 'S' => 0.10542],
            ['month' => 12, 'L' => 0.0732, 'M' => 9.6479, 'S' => 0.10391],
            ['month' => 18, 'L' => 0.0356, 'M' => 10.8584,'S' => 0.10334],
            ['month' => 24, 'L' => 0.0107, 'M' => 12.1515,'S' => 0.10394],
            ['month' => 36, 'L' =>-0.0240, 'M' => 14.3340,'S' => 0.10736],
            ['month' => 48, 'L' =>-0.0487, 'M' => 16.3489,'S' => 0.11164],
            ['month' => 60, 'L' =>-0.0676, 'M' => 18.3670,'S' => 0.11645],
            ['month' => 72, 'L' =>-0.0820, 'M' => 20.5278,'S' => 0.12148],
            ['month' => 84, 'L' =>-0.0930, 'M' => 22.9191,'S' => 0.12636],
            ['month' => 96, 'L' =>-0.1010, 'M' => 25.6030,'S' => 0.13112],
            ['month' => 108,'L' =>-0.1060, 'M' => 28.6128,'S' => 0.13572],
            ['month' => 120,'L' =>-0.1090, 'M' => 31.9447,'S' => 0.14005],
        ],
        'female' => [
            ['month' => 0,  'L' => 0.3809, 'M' => 3.2322, 'S' => 0.14171],
            ['month' => 1,  'L' => 0.1714, 'M' => 4.1873, 'S' => 0.13724],
            ['month' => 2,  'L' => 0.0962, 'M' => 5.1282, 'S' => 0.12950],
            ['month' => 3,  'L' => 0.0402, 'M' => 5.8458, 'S' => 0.12369],
            ['month' => 4,  'L' =>-0.0050, 'M' => 6.4237, 'S' => 0.11946],
            ['month' => 6,  'L' =>-0.0700, 'M' => 7.2970, 'S' => 0.11404],
            ['month' => 9,  'L' =>-0.1220, 'M' => 8.2010, 'S' => 0.10938],
            ['month' => 12, 'L' =>-0.1560, 'M' => 8.9481, 'S' => 0.10675],
            ['month' => 18, 'L' =>-0.2000, 'M' => 10.2000,'S' => 0.10396],
            ['month' => 24, 'L' =>-0.2300, 'M' => 11.5000,'S' => 0.10335],
            ['month' => 36, 'L' =>-0.2650, 'M' => 13.9000,'S' => 0.10614],
            ['month' => 48, 'L' =>-0.2870, 'M' => 16.0600,'S' => 0.11050],
            ['month' => 60, 'L' =>-0.3030, 'M' => 18.2600,'S' => 0.11541],
            ['month' => 72, 'L' =>-0.3140, 'M' => 20.5500,'S' => 0.12058],
            ['month' => 84, 'L' =>-0.3200, 'M' => 23.0500,'S' => 0.12566],
            ['month' => 96, 'L' =>-0.3240, 'M' => 25.8700,'S' => 0.13055],
            ['month' => 108,'L' =>-0.3250, 'M' => 29.0200,'S' => 0.13518],
            ['month' => 120,'L' =>-0.3240, 'M' => 32.5500,'S' => 0.13940],
        ],
    ];

    const HEIGHT_FOR_AGE = [
        'male' => [
            ['month' => 0,  'L' => 1, 'M' => 49.8842, 'S' => 0.03795],
            ['month' => 1,  'L' => 1, 'M' => 54.7244, 'S' => 0.03557],
            ['month' => 2,  'L' => 1, 'M' => 58.4249, 'S' => 0.03424],
            ['month' => 3,  'L' => 1, 'M' => 61.4292, 'S' => 0.03328],
            ['month' => 4,  'L' => 1, 'M' => 63.8860, 'S' => 0.03257],
            ['month' => 6,  'L' => 1, 'M' => 67.6236, 'S' => 0.03169],
            ['month' => 9,  'L' => 1, 'M' => 72.0000, 'S' => 0.03092],
            ['month' => 12, 'L' => 1, 'M' => 75.7486, 'S' => 0.03036],
            ['month' => 18, 'L' => 1, 'M' => 82.2515, 'S' => 0.02970],
            ['month' => 24, 'L' => 1, 'M' => 87.8161, 'S' => 0.03022],
            ['month' => 36, 'L' => 1, 'M' => 96.1000, 'S' => 0.03078],
            ['month' => 48, 'L' => 1, 'M' => 103.300, 'S' => 0.03152],
            ['month' => 60, 'L' => 1, 'M' => 110.000, 'S' => 0.03244],
            ['month' => 72, 'L' => 1, 'M' => 116.000, 'S' => 0.03341],
            ['month' => 84, 'L' => 1, 'M' => 121.700, 'S' => 0.03433],
            ['month' => 96, 'L' => 1, 'M' => 127.300, 'S' => 0.03519],
            ['month' => 108,'L' => 1, 'M' => 132.600, 'S' => 0.03601],
            ['month' => 120,'L' => 1, 'M' => 137.800, 'S' => 0.03681],
            ['month' => 144,'L' => 1, 'M' => 149.100, 'S' => 0.03878],
            ['month' => 168,'L' => 1, 'M' => 163.200, 'S' => 0.03929],
            ['month' => 192,'L' => 1, 'M' => 173.400, 'S' => 0.03716],
            ['month' => 228,'L' => 1, 'M' => 176.100, 'S' => 0.03620],
        ],
        'female' => [
            ['month' => 0,  'L' => 1, 'M' => 49.1477, 'S' => 0.03790],
            ['month' => 1,  'L' => 1, 'M' => 53.6872, 'S' => 0.03614],
            ['month' => 2,  'L' => 1, 'M' => 57.0673, 'S' => 0.03496],
            ['month' => 3,  'L' => 1, 'M' => 59.8029, 'S' => 0.03402],
            ['month' => 4,  'L' => 1, 'M' => 62.0899, 'S' => 0.03327],
            ['month' => 6,  'L' => 1, 'M' => 65.7311, 'S' => 0.03231],
            ['month' => 9,  'L' => 1, 'M' => 70.1000, 'S' => 0.03137],
            ['month' => 12, 'L' => 1, 'M' => 73.9000, 'S' => 0.03076],
            ['month' => 18, 'L' => 1, 'M' => 80.7000, 'S' => 0.03008],
            ['month' => 24, 'L' => 1, 'M' => 86.4000, 'S' => 0.03046],
            ['month' => 36, 'L' => 1, 'M' => 95.1000, 'S' => 0.03109],
            ['month' => 48, 'L' => 1, 'M' => 102.700, 'S' => 0.03188],
            ['month' => 60, 'L' => 1, 'M' => 109.400, 'S' => 0.03283],
            ['month' => 72, 'L' => 1, 'M' => 115.500, 'S' => 0.03384],
            ['month' => 84, 'L' => 1, 'M' => 121.500, 'S' => 0.03479],
            ['month' => 96, 'L' => 1, 'M' => 127.300, 'S' => 0.03569],
            ['month' => 108,'L' => 1, 'M' => 133.000, 'S' => 0.03650],
            ['month' => 120,'L' => 1, 'M' => 138.600, 'S' => 0.03722],
            ['month' => 144,'L' => 1, 'M' => 151.200, 'S' => 0.03843],
            ['month' => 168,'L' => 1, 'M' => 159.800, 'S' => 0.03747],
            ['month' => 192,'L' => 1, 'M' => 163.300, 'S' => 0.03599],
            ['month' => 228,'L' => 1, 'M' => 163.800, 'S' => 0.03570],
        ],
    ];

    const HEAD_FOR_AGE = [
        'male' => [
            ['month' => 0,  'L' => 1, 'M' => 34.4618, 'S' => 0.03686],
            ['month' => 1,  'L' => 1, 'M' => 37.2759, 'S' => 0.03133],
            ['month' => 2,  'L' => 1, 'M' => 39.1285, 'S' => 0.02997],
            ['month' => 3,  'L' => 1, 'M' => 40.5135, 'S' => 0.02918],
            ['month' => 4,  'L' => 1, 'M' => 41.6317, 'S' => 0.02868],
            ['month' => 6,  'L' => 1, 'M' => 43.3306, 'S' => 0.02808],
            ['month' => 9,  'L' => 1, 'M' => 45.1900, 'S' => 0.02758],
            ['month' => 12, 'L' => 1, 'M' => 46.4900, 'S' => 0.02730],
            ['month' => 18, 'L' => 1, 'M' => 48.1300, 'S' => 0.02695],
            ['month' => 24, 'L' => 1, 'M' => 49.0000, 'S' => 0.02671],
            ['month' => 36, 'L' => 1, 'M' => 50.2100, 'S' => 0.02647],
            ['month' => 48, 'L' => 1, 'M' => 50.9800, 'S' => 0.02632],
            ['month' => 60, 'L' => 1, 'M' => 51.5200, 'S' => 0.02620],
        ],
        'female' => [
            ['month' => 0,  'L' => 1, 'M' => 33.8787, 'S' => 0.03496],
            ['month' => 1,  'L' => 1, 'M' => 36.5463, 'S' => 0.03080],
            ['month' => 2,  'L' => 1, 'M' => 38.2521, 'S' => 0.02960],
            ['month' => 3,  'L' => 1, 'M' => 39.5328, 'S' => 0.02886],
            ['month' => 4,  'L' => 1, 'M' => 40.5817, 'S' => 0.02838],
            ['month' => 6,  'L' => 1, 'M' => 42.1800, 'S' => 0.02779],
            ['month' => 9,  'L' => 1, 'M' => 43.8000, 'S' => 0.02730],
            ['month' => 12, 'L' => 1, 'M' => 45.0000, 'S' => 0.02702],
            ['month' => 18, 'L' => 1, 'M' => 46.7500, 'S' => 0.02661],
            ['month' => 24, 'L' => 1, 'M' => 47.7500, 'S' => 0.02638],
            ['month' => 36, 'L' => 1, 'M' => 49.0500, 'S' => 0.02614],
            ['month' => 48, 'L' => 1, 'M' => 49.8700, 'S' => 0.02600],
            ['month' => 60, 'L' => 1, 'M' => 50.4700, 'S' => 0.02590],
        ],
    ];

    const BMI_FOR_AGE = [
        'male' => [
            ['month' => 0,  'L' =>-0.0631, 'M' => 13.4069, 'S' => 0.09210],
            ['month' => 1,  'L' => 0.6697, 'M' => 14.9441, 'S' => 0.08434],
            ['month' => 2,  'L' => 0.6120, 'M' => 16.3017, 'S' => 0.08034],
            ['month' => 3,  'L' => 0.2959, 'M' => 16.8828, 'S' => 0.07950],
            ['month' => 4,  'L' => 0.0356, 'M' => 17.1128, 'S' => 0.07930],
            ['month' => 6,  'L' =>-0.3530, 'M' => 17.2500, 'S' => 0.07940],
            ['month' => 9,  'L' =>-0.6900, 'M' => 17.1000, 'S' => 0.07980],
            ['month' => 12, 'L' =>-0.8660, 'M' => 16.8200, 'S' => 0.08070],
            ['month' => 18, 'L' =>-1.0300, 'M' => 16.1400, 'S' => 0.08210],
            ['month' => 24, 'L' =>-1.0700, 'M' => 15.7500, 'S' => 0.08390],
            ['month' => 36, 'L' =>-0.9960, 'M' => 15.5200, 'S' => 0.08810],
            ['month' => 48, 'L' =>-0.8660, 'M' => 15.5100, 'S' => 0.09290],
            ['month' => 60, 'L' =>-0.7150, 'M' => 15.5200, 'S' => 0.09780],
            ['month' => 72, 'L' =>-0.5800, 'M' => 15.6800, 'S' => 0.10220],
            ['month' => 84, 'L' =>-0.4600, 'M' => 15.9500, 'S' => 0.10600],
            ['month' => 96, 'L' =>-0.3500, 'M' => 16.3000, 'S' => 0.10920],
            ['month' => 108,'L' =>-0.2500, 'M' => 16.7200, 'S' => 0.11180],
            ['month' => 120,'L' =>-0.1600, 'M' => 17.2000, 'S' => 0.11400],
        ],
        'female' => [
            ['month' => 0,  'L' => 0.0506, 'M' => 13.3363, 'S' => 0.09281],
            ['month' => 1,  'L' => 0.4050, 'M' => 14.5661, 'S' => 0.08577],
            ['month' => 2,  'L' => 0.1960, 'M' => 15.7973, 'S' => 0.08332],
            ['month' => 3,  'L' =>-0.0800, 'M' => 16.3500, 'S' => 0.08350],
            ['month' => 4,  'L' =>-0.2900, 'M' => 16.5800, 'S' => 0.08370],
            ['month' => 6,  'L' =>-0.5900, 'M' => 16.7000, 'S' => 0.08400],
            ['month' => 9,  'L' =>-0.8000, 'M' => 16.5000, 'S' => 0.08460],
            ['month' => 12, 'L' =>-0.9200, 'M' => 16.2200, 'S' => 0.08540],
            ['month' => 18, 'L' =>-1.0300, 'M' => 15.6500, 'S' => 0.08710],
            ['month' => 24, 'L' =>-1.0400, 'M' => 15.3600, 'S' => 0.08890],
            ['month' => 36, 'L' =>-0.9600, 'M' => 15.2200, 'S' => 0.09290],
            ['month' => 48, 'L' =>-0.8300, 'M' => 15.2600, 'S' => 0.09720],
            ['month' => 60, 'L' =>-0.6800, 'M' => 15.3100, 'S' => 0.10130],
            ['month' => 72, 'L' =>-0.5400, 'M' => 15.5000, 'S' => 0.10510],
            ['month' => 84, 'L' =>-0.4200, 'M' => 15.7800, 'S' => 0.10850],
            ['month' => 96, 'L' =>-0.3100, 'M' => 16.1500, 'S' => 0.11140],
            ['month' => 108,'L' =>-0.2100, 'M' => 16.6200, 'S' => 0.11400],
            ['month' => 120,'L' =>-0.1200, 'M' => 17.1500, 'S' => 0.11620],
        ],
    ];
}

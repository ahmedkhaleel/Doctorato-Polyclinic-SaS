<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Pure obstetric date/measurement maths — the OB/GYN analogue of
 * WhoGrowthStandardsService. No DB access; fully unit-testable.
 *
 *  - EDD from LMP (Naegele's rule)
 *  - Gestational age (weeks + days) on any date
 *  - Trimester
 *  - Expected fundal height
 *  - WHO 8-contact antenatal schedule (upcoming visits)
 *  - Rough EFW assessment vs. a Hadlock-style expected weight
 */
class ObstetricCalculatorService
{
    /** Standard pregnancy length in days (40 weeks). */
    public const GESTATION_DAYS = 280;

    /** WHO 2016 "8-contact" antenatal visit schedule (gestational weeks). */
    public const WHO_ANC_WEEKS = [12, 20, 26, 30, 34, 36, 38, 40];

    public function eddFromLmp(Carbon|string $lmp): Carbon
    {
        return $this->toCarbon($lmp)->copy()->addDays(self::GESTATION_DAYS);
    }

    public function lmpFromEdd(Carbon|string $edd): Carbon
    {
        return $this->toCarbon($edd)->copy()->subDays(self::GESTATION_DAYS);
    }

    /**
     * Gestational age on $onDate (default today). Negative days before LMP are
     * clamped to zero; capped at 42w for sanity.
     *
     * @return array{weeks:int, days:int, decimal:float, total_days:int}
     */
    public function gestationalAge(Carbon|string $lmp, Carbon|string|null $onDate = null): array
    {
        $lmp = $this->toCarbon($lmp);
        $on = $onDate ? $this->toCarbon($onDate) : Carbon::today();

        $totalDays = (int) max(0, $lmp->diffInDays($on, false));
        $totalDays = min($totalDays, 42 * 7); // cap at 42 weeks

        return [
            'weeks' => intdiv($totalDays, 7),
            'days' => $totalDays % 7,
            'decimal' => round($totalDays / 7, 1),
            'total_days' => $totalDays,
        ];
    }

    /** "32w+4d" style label. */
    public function gestationalAgeLabel(Carbon|string $lmp, Carbon|string|null $onDate = null): string
    {
        $ga = $this->gestationalAge($lmp, $onDate);

        return "{$ga['weeks']}w+{$ga['days']}d";
    }

    public function trimester(float $weeks): int
    {
        if ($weeks < 14) {
            return 1;
        }
        if ($weeks < 28) {
            return 2;
        }

        return 3;
    }

    /**
     * Expected symphysis-fundal height (cm) ≈ gestational age in weeks between
     * 20 and 36 weeks (±2cm clinically). Null outside that window.
     */
    public function expectedFundalHeight(float $weeks): ?int
    {
        if ($weeks < 20 || $weeks > 36) {
            return null;
        }

        return (int) round($weeks);
    }

    /**
     * Upcoming WHO antenatal contacts after the current gestational age.
     *
     * @return int[] weeks still ahead
     */
    public function upcomingAncWeeks(float $currentWeeks): array
    {
        return array_values(array_filter(
            self::WHO_ANC_WEEKS,
            fn ($w) => $w > $currentWeeks
        ));
    }

    /**
     * Approximate date of the next recommended antenatal visit, or null if the
     * full schedule is complete.
     */
    public function nextAncDate(Carbon|string $lmp, Carbon|string|null $onDate = null): ?Carbon
    {
        $ga = $this->gestationalAge($lmp, $onDate);
        $upcoming = $this->upcomingAncWeeks($ga['decimal']);

        if (empty($upcoming)) {
            return null;
        }

        return $this->toCarbon($lmp)->copy()->addWeeks($upcoming[0]);
    }

    /**
     * Days remaining until the EDD (negative = overdue).
     */
    public function daysUntilEdd(Carbon|string $edd, Carbon|string|null $onDate = null): int
    {
        $on = $onDate ? $this->toCarbon($onDate) : Carbon::today();

        return $on->diffInDays($this->toCarbon($edd), false);
    }

    /**
     * Hadlock-style expected EFW (grams) for a given gestational week, then a
     * coarse small/normal/large assessment. Intentionally approximate — for UI
     * flagging only, not a clinical percentile.
     *
     * @return array{expected:int, status:string}
     */
    public function efwAssessment(float $weeks, ?int $efwGrams): array
    {
        // Smoothed Hadlock median EFW by completed week (24–40).
        $median = [
            24 => 670, 25 => 760, 26 => 880, 27 => 1000, 28 => 1150, 29 => 1310,
            30 => 1460, 31 => 1630, 32 => 1810, 33 => 2010, 34 => 2220, 35 => 2430,
            36 => 2660, 37 => 2860, 38 => 3080, 39 => 3290, 40 => 3460,
        ];

        $w = (int) round($weeks);
        $expected = $median[$w] ?? ($w < 24 ? 600 : 3500);

        if ($efwGrams === null) {
            return ['expected' => $expected, 'status' => 'unknown'];
        }

        $ratio = $efwGrams / $expected;
        $status = match (true) {
            $ratio < 0.85 => 'small',   // possible IUGR
            $ratio > 1.15 => 'large',   // possible macrosomia
            default => 'normal',
        };

        return ['expected' => $expected, 'status' => $status];
    }

    private function toCarbon(Carbon|string $date): Carbon
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }
}

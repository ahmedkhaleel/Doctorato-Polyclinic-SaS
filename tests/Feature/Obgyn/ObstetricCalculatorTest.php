<?php

namespace Tests\Feature\Obgyn;

use App\Services\ObstetricCalculatorService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pins the pure obstetric maths used across the OB/GYN module.
 */
class ObstetricCalculatorTest extends TestCase
{
    private ObstetricCalculatorService $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new ObstetricCalculatorService;
    }

    #[Test]
    public function edd_is_lmp_plus_280_days_naegele(): void
    {
        $edd = $this->calc->eddFromLmp('2026-01-01');
        $this->assertSame('2026-10-08', $edd->toDateString()); // 2026-01-01 + 280d
    }

    #[Test]
    public function lmp_and_edd_are_inverse(): void
    {
        $lmp = '2026-03-15';
        $edd = $this->calc->eddFromLmp($lmp);
        $this->assertSame($lmp, $this->calc->lmpFromEdd($edd)->toDateString());
    }

    #[Test]
    public function gestational_age_counts_weeks_and_days(): void
    {
        // 100 days after LMP = 14 weeks + 2 days
        $ga = $this->calc->gestationalAge('2026-01-01', '2026-04-11');
        $this->assertSame(14, $ga['weeks']);
        $this->assertSame(2, $ga['days']);
        $this->assertSame(100, $ga['total_days']);
        $this->assertSame('14w+2d', $this->calc->gestationalAgeLabel('2026-01-01', '2026-04-11'));
    }

    #[Test]
    public function trimester_boundaries(): void
    {
        $this->assertSame(1, $this->calc->trimester(13.9));
        $this->assertSame(2, $this->calc->trimester(14));
        $this->assertSame(2, $this->calc->trimester(27.9));
        $this->assertSame(3, $this->calc->trimester(28));
    }

    #[Test]
    public function expected_fundal_height_matches_weeks_in_window_only(): void
    {
        $this->assertNull($this->calc->expectedFundalHeight(18));
        $this->assertSame(28, $this->calc->expectedFundalHeight(28));
        $this->assertNull($this->calc->expectedFundalHeight(38));
    }

    #[Test]
    public function upcoming_anc_weeks_filters_past_contacts(): void
    {
        $this->assertSame([30, 34, 36, 38, 40], $this->calc->upcomingAncWeeks(27));
        $this->assertSame([], $this->calc->upcomingAncWeeks(41));
    }

    #[Test]
    public function next_anc_date_is_anchored_to_lmp(): void
    {
        // At 27w the next WHO contact is week 30 → LMP + 30 weeks.
        $next = $this->calc->nextAncDate('2026-01-01', Carbon::parse('2026-01-01')->addWeeks(27));
        $this->assertSame(Carbon::parse('2026-01-01')->addWeeks(30)->toDateString(), $next->toDateString());
    }

    #[Test]
    public function efw_assessment_flags_small_and_large(): void
    {
        $this->assertSame('normal', $this->calc->efwAssessment(32, 1810)['status']);
        $this->assertSame('small', $this->calc->efwAssessment(32, 1200)['status']);
        $this->assertSame('large', $this->calc->efwAssessment(32, 2300)['status']);
        $this->assertSame('unknown', $this->calc->efwAssessment(32, null)['status']);
    }
}

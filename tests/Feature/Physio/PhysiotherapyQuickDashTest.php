<?php

namespace Tests\Feature\Physio;

use App\Services\NeuroPsych\ScaleEngine;
use Tests\TestCase;

/**
 * F-E — QuickDASH uses the new mean_scaled scoring mode: ((mean − 1)/4) × 100.
 * Sum-scored scales (PHQ-9, ODI) are unaffected.
 */
class PhysiotherapyQuickDashTest extends TestCase
{
    public function test_quickdash_is_registered_for_physiotherapy(): void
    {
        $this->assertArrayHasKey('quickdash', ScaleEngine::forModule('physiotherapy'));
        $this->assertSame(11, ScaleEngine::mcid('quickdash'));
        $this->assertFalse(ScaleEngine::higherIsBetter('quickdash'));
    }

    public function test_mean_scaled_scoring(): void
    {
        // All 11 items = 3 → mean 3 → ((3-1)/4)*100 = 50 → Moderate.
        $this->assertSame(50, ScaleEngine::score('quickdash', array_fill(0, 11, 3)));
        $this->assertSame('Moderate disability', ScaleEngine::severity('quickdash', 50)['en']);

        // All = 1 → 0 (minimal); all = 5 → 100 (severe).
        $this->assertSame(0, ScaleEngine::score('quickdash', array_fill(0, 11, 1)));
        $this->assertSame(100, ScaleEngine::score('quickdash', array_fill(0, 11, 5)));

        // Partial completion uses the mean of answered items only.
        $this->assertSame(75, ScaleEngine::score('quickdash', [0 => 4, 1 => 4, 2 => 4]));
    }

    public function test_sum_scales_are_unaffected(): void
    {
        // ODI stays sum-scored.
        $this->assertSame(30, ScaleEngine::score('odi', array_fill(0, 10, 3)));
        // PHQ-9 stays sum-scored.
        $this->assertSame(27, ScaleEngine::score('phq9', array_fill(0, 9, 3)));
    }
}

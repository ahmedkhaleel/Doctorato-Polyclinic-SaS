<?php

namespace Tests\Feature\Physio;

use App\Services\NeuroPsych\ScaleEngine;
use Tests\TestCase;

/** G-B — WOMAC (knee/hip OA): 24 items × 0–4 → sum 0–96, higher = worse. */
class PhysiotherapyWomacTest extends TestCase
{
    public function test_womac_registered_and_scored(): void
    {
        $this->assertArrayHasKey('womac', ScaleEngine::forModule('physiotherapy'));
        $this->assertCount(24, ScaleEngine::definition('womac')['items']);
        $this->assertFalse(ScaleEngine::higherIsBetter('womac'));
        $this->assertSame(12, ScaleEngine::mcid('womac'));

        // All 24 items = 2 → 48 → "Mild" (25–48).
        $this->assertSame(48, ScaleEngine::score('womac', array_fill(0, 24, 2)));
        $this->assertSame('Mild', ScaleEngine::severity('womac', 48)['en']);
        // All = 4 → 96 → Severe.
        $this->assertSame(96, ScaleEngine::score('womac', array_fill(0, 24, 4)));
        $this->assertSame('Severe', ScaleEngine::severity('womac', 96)['en']);
    }
}

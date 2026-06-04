<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Patient;
use App\Models\ScaleResult;
use App\Services\NeuroPsych\ScaleEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP2 — measurement-based-care scoring correctness (clinically critical) plus
 * result persistence. Scoring lives in code, so these tests pin the bands.
 */
class Np2ScaleEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_phq9_severe_and_suicidality_flag(): void
    {
        // All 9 items = 3 → 27 → severe; item 9 (index 8) non-zero → flag.
        $answers = array_fill(0, 9, 3);
        $this->assertSame(27, ScaleEngine::score('phq9', $answers));
        $this->assertSame('Severe', ScaleEngine::severity('phq9', 27)['en']);
        $this->assertTrue(ScaleEngine::raisesFlag('phq9', $answers));
    }

    public function test_phq9_minimal_no_flag(): void
    {
        $answers = array_fill(0, 9, 0);
        $this->assertSame(0, ScaleEngine::score('phq9', $answers));
        $this->assertSame('Minimal', ScaleEngine::severity('phq9', 0)['en']);
        $this->assertFalse(ScaleEngine::raisesFlag('phq9', $answers));
    }

    public function test_phq9_flag_only_from_item_9(): void
    {
        // High on items 1-8 but item 9 = 0 → no suicidality flag.
        $answers = [3, 3, 3, 3, 3, 3, 3, 3, 0];
        $this->assertSame(24, ScaleEngine::score('phq9', $answers));
        $this->assertFalse(ScaleEngine::raisesFlag('phq9', $answers));
    }

    public function test_gad7_moderate_band(): void
    {
        $answers = [2, 2, 2, 2, 2, 1, 1]; // 12 → moderate (10-14)
        $this->assertSame(12, ScaleEngine::score('gad7', $answers));
        $this->assertSame('Moderate', ScaleEngine::severity('gad7', 12)['en']);
    }

    public function test_hit6_weighted_severe(): void
    {
        $answers = array_fill(0, 6, 13); // all "always" → 78 → severe impact
        $this->assertSame(78, ScaleEngine::score('hit6', $answers));
        $this->assertSame('Severe impact', ScaleEngine::severity('hit6', 78)['en']);
    }

    public function test_for_module_filters_by_track(): void
    {
        $psych = ScaleEngine::forModule('psychiatry');
        $neuro = ScaleEngine::forModule('neurology');
        $this->assertArrayHasKey('phq9', $psych);
        $this->assertArrayHasKey('gad7', $psych);
        $this->assertArrayNotHasKey('hit6', $psych);
        $this->assertArrayHasKey('hit6', $neuro);
    }

    public function test_result_build_persists_computed_fields(): void
    {
        $p = new Patient(['full_name' => 'S', 'phone' => '0500003333', 'gender' => 'female']);
        $p->file_number = 'PAT-SC-001';
        $p->is_active = true;
        $p->save();

        $result = ScaleResult::build($p->id, 'phq9', array_fill(0, 9, 2), 'patient');
        $result->save();

        $this->assertDatabaseHas('scale_results', [
            'patient_id' => $p->id, 'scale_key' => 'phq9', 'score' => 18,
            'severity' => 'Moderately severe', 'entered_by' => 'patient',
        ]);
        // item 9 = 2 → flag
        $this->assertTrue($result->fresh()->flag);
    }
}

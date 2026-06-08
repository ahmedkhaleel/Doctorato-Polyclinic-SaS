<?php

namespace Tests\Feature;

use App\Models\DentalChart;
use Database\Seeders\SpecialtyDoctorDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards against the blank-dental-chart crash: the odontogram UI maps a tooth's
 * `condition` to a colour theme keyed by exactly these values, then reads
 * `.fill` — an unknown condition (e.g. the old demo 'caries') is undefined and
 * crashes the whole page. So every seeded DentalChart condition must be a known
 * key (the Vue side also normalizes defensively at runtime).
 */
class DentalChartConditionTest extends TestCase
{
    use RefreshDatabase;

    /** Must mirror conditionTheme in resources/js/Pages/Doctor/Dental/DentalChart/Show.vue. */
    private array $known = ['healthy', 'decayed', 'filled', 'missing', 'crown', 'bridge', 'implant', 'root_canal', 'extracted'];

    public function test_demo_dental_chart_uses_only_known_conditions(): void
    {
        $this->seed(SpecialtyDoctorDemoSeeder::class);

        $conditions = DentalChart::query()->distinct()->pluck('condition')->filter()->all();
        $this->assertNotEmpty($conditions, 'demo should seed some dental chart teeth');

        $unknown = array_values(array_diff($conditions, $this->known));
        $this->assertSame([], $unknown, 'Seeded dental conditions outside the odontogram theme map: '.implode(', ', $unknown));
    }
}

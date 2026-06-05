<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\Service;
use Database\Seeders\NeuropsychDemoSeeder;
use Database\Seeders\NeuropsychServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G-SEED — enabling psychiatry/neurology yields bookable doctors + services
 * (no empty booking page). Seeders are idempotent.
 */
class NeuropsychSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_seeder_creates_bookable_services(): void
    {
        $this->seed(NeuropsychServiceSeeder::class);

        $this->assertGreaterThanOrEqual(3, Service::where('module', 'psychiatry')->where('bookable', true)->count());
        $this->assertGreaterThanOrEqual(4, Service::where('module', 'neurology')->where('bookable', true)->count());
    }

    public function test_demo_seeder_creates_specialty_doctors(): void
    {
        $this->seed(NeuropsychDemoSeeder::class);

        $this->assertSame(1, Doctor::where('module', 'psychiatry')->count());
        $this->assertSame(1, Doctor::where('module', 'neurology')->count());
        $this->assertEqualsWithDelta(300, (float) Doctor::where('module', 'psychiatry')->value('psychiatry_consultation_fee'), 0.01);
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed(NeuropsychServiceSeeder::class);
        $this->seed(NeuropsychServiceSeeder::class);
        $this->seed(NeuropsychDemoSeeder::class);
        $this->seed(NeuropsychDemoSeeder::class);

        // No duplication on re-run.
        $this->assertSame(1, Doctor::where('module', 'neurology')->count());
        $this->assertSame(3, Service::where('module', 'psychiatry')->count());
    }
}

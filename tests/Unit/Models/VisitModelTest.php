<?php

namespace Tests\Unit\Models;

use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_amount_is_not_mass_assignable(): void
    {
        $this->assertNotContains('commission_amount', (new Visit)->getFillable());
    }

    public function test_commission_rate_is_not_mass_assignable(): void
    {
        $this->assertNotContains('commission_rate', (new Visit)->getFillable());
    }

    public function test_mass_assignment_cannot_set_commission(): void
    {
        $visit = new Visit([
            'patient_id' => 1,
            'doctor_id' => 1,
            'visit_type' => 'consultation',
            'commission_amount' => 9999,
            'commission_rate' => 100,
        ]);

        $this->assertNull($visit->commission_amount);
        $this->assertNull($visit->commission_rate);
    }
}

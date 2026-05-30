<?php

namespace Tests\Feature\Obgyn;

use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P1 foundation: module registration + schema + model relations all wire up.
 */
class ObgynFoundationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function obgyn_is_registered_as_a_medical_module(): void
    {
        $this->assertArrayHasKey('obgyn', ModuleManager::getAllModules());
        $this->assertContains('obgyn', ModuleManager::MEDICAL_MODULES);
    }

    #[Test]
    public function pregnancy_owns_antenatal_visits_and_delivery(): void
    {
        $patient = Patient::create(['full_name' => 'Test Mother', 'phone' => '01000000001', 'gender' => 'female']);

        $pregnancy = Pregnancy::create([
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'status' => Pregnancy::STATUS_ACTIVE,
        ]);

        AntenatalVisit::create([
            'pregnancy_id' => $pregnancy->id,
            'visit_date' => '2026-04-01',
            'phase' => AntenatalVisit::PHASE_ANTENATAL,
            'weight_kg' => 62.5,
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
        ]);

        $this->assertCount(1, $pregnancy->antenatalVisits);
        $this->assertSame('120/80', $pregnancy->antenatalVisits->first()->blood_pressure);

        // Patient relations resolve.
        $this->assertTrue($patient->pregnancies()->exists());
        $this->assertTrue($patient->activePregnancy->is($pregnancy));
    }

    #[Test]
    public function delivering_a_pregnancy_links_one_to_one(): void
    {
        $patient = Patient::create(['full_name' => 'Mother Two', 'phone' => '01000000002', 'gender' => 'female']);
        $pregnancy = Pregnancy::create(['patient_id' => $patient->id, 'status' => Pregnancy::STATUS_ACTIVE]);

        DeliveryRecord::create([
            'pregnancy_id' => $pregnancy->id,
            'delivery_date' => '2026-10-05',
            'delivery_mode' => DeliveryRecord::MODE_NVD,
            'outcome' => 'live',
            'baby_weight_grams' => 3200,
        ]);
        $pregnancy->update(['status' => Pregnancy::STATUS_DELIVERED]);

        $this->assertInstanceOf(DeliveryRecord::class, $pregnancy->fresh()->delivery);
        $this->assertFalse($patient->activePregnancy()->exists());
    }
}

<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Services\CommissionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R1 — consultation fee + commission resolution covers obgyn/psychiatry/
 * neurology (doctor-level override, then module-level fee fallback).
 */
class CommissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_fee_prefers_doctor_then_module(): void
    {
        $calc = app(CommissionCalculator::class);

        $withFee = Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc A', 'status' => 'active', 'module' => 'psychiatry', 'psychiatry_consultation_fee' => 350]);
        $this->assertEqualsWithDelta(350, $calc->getConsultationFee($withFee, 'psychiatry'), 0.01);

        // No doctor fee → module_settings consultation_fee (300, seeded by migration).
        $noFee = Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc B', 'status' => 'active', 'module' => 'neurology']);
        $this->assertEqualsWithDelta(300, $calc->getConsultationFee($noFee, 'neurology'), 0.01);
    }

    public function test_consultation_commission_uses_specialty_rate(): void
    {
        $calc = app(CommissionCalculator::class);

        $doc = Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc C', 'status' => 'active', 'module' => 'neurology', 'neurology_consultation_commission' => 45]);
        $this->assertEqualsWithDelta(45, $calc->getConsultationCommissionRate($doc, 'neurology'), 0.01);
    }
}

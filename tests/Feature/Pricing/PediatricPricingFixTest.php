<?php

namespace Tests\Feature\Pricing;

use App\Models\Doctor;
use App\Models\Setting;
use App\Services\ModuleManager;
use App\Services\Pricing\PricingResolver;
use App\Services\Pricing\PricingSettingsMirror;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pediatric pricing reconciliation — the editor saves `pediatric_consultation_fee`;
 * the resolver now reads that key (previously read `pediatric_consultant_fee`,
 * which the editor never wrote → pediatric resolved to 0). Honours the admin's
 * existing input.
 */
class PediatricPricingFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::clearCache();
    }

    public function test_pediatric_consultation_fee_now_resolves_from_editor_key(): void
    {
        Setting::set('pediatric_consultation_fee', 180, 'pricing');

        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'pediatric', 'doctor_type' => 'consultant']);
        $fee = app(PricingResolver::class)->consultationFee($doctor, 'pediatric');

        $this->assertEqualsWithDelta(180, $fee, 0.01);
    }

    public function test_pediatric_resolves_zero_only_when_unset(): void
    {
        // No pediatric_consultation_fee → still 0 (no invented value).
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'pediatric', 'doctor_type' => 'consultant']);
        $this->assertEqualsWithDelta(0, app(PricingResolver::class)->consultationFee($doctor, 'pediatric'), 0.01);
    }

    public function test_mirror_propagates_pediatric_consultation_fee(): void
    {
        Setting::set('pediatric_consultation_fee', 200, 'pricing');
        app(PricingSettingsMirror::class)->mirror();
        ModuleManager::clearCache();

        $this->assertEqualsWithDelta(200, (float) ModuleManager::getSetting('pediatric', 'consultant_fee'), 0.01);
        $this->assertEqualsWithDelta(200, (float) ModuleManager::getSetting('pediatric', 'consultation_fee'), 0.01);
    }
}

<?php

namespace Tests\Feature\Pricing;

use App\Models\Doctor;
use App\Models\Setting;
use App\Services\ModuleManager;
use App\Services\Pricing\PricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADR-001 Phase 1 — the backfill must populate module_settings from the legacy
 * fee Settings, be idempotent, and leave resolved prices UNCHANGED (the read
 * path still uses the legacy store in Phase 1).
 */
class PricingBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::clearCache();

        // Seed legacy dental fees (the resolver reads these today).
        Setting::set('dental_consultant_fee', 400, 'pricing');
        Setting::set('dental_specialist_fee', 300, 'pricing');
        Setting::set('followup_fee', 120, 'pricing');
        Setting::set('followup_window_days', 21, 'pricing');
    }

    public function test_backfill_populates_module_settings_from_legacy(): void
    {
        $this->artisan('pricing:backfill-module-settings')->assertExitCode(0);
        ModuleManager::clearCache();

        $this->assertEqualsWithDelta(400, (float) ModuleManager::getSetting('dental', 'consultant_fee'), 0.01);
        $this->assertEqualsWithDelta(300, (float) ModuleManager::getSetting('dental', 'specialist_fee'), 0.01);
        $this->assertEqualsWithDelta(120, (float) ModuleManager::getSetting('dental', 'followup_fee'), 0.01);
        $this->assertSame(21, (int) ModuleManager::getSetting('dental', 'followup_window_days'));
    }

    public function test_backfill_does_not_change_resolved_prices(): void
    {
        $resolver = app(PricingResolver::class);
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'dental', 'doctor_type' => 'consultant']);

        $before = $resolver->consultationFee($doctor, 'dental');

        $this->artisan('pricing:backfill-module-settings')->assertExitCode(0);
        ModuleManager::clearCache();

        // Read path is still legacy in Phase 1 → resolved fee is identical.
        $after = app(PricingResolver::class)->consultationFee($doctor->fresh(), 'dental');
        $this->assertEqualsWithDelta($before, $after, 0.01);
        $this->assertEqualsWithDelta(400, $after, 0.01);
    }

    public function test_backfill_is_idempotent(): void
    {
        $this->artisan('pricing:backfill-module-settings')->assertExitCode(0);
        $this->artisan('pricing:backfill-module-settings')->assertExitCode(0);
        ModuleManager::clearCache();

        $this->assertEqualsWithDelta(400, (float) ModuleManager::getSetting('dental', 'consultant_fee'), 0.01);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('pricing:backfill-module-settings --dry-run')->assertExitCode(0);
        ModuleManager::clearCache();

        $this->assertNull(ModuleManager::getSetting('dental', 'consultant_fee'));
    }
}

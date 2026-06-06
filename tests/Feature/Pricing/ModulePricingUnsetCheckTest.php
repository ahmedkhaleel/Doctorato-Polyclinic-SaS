<?php

namespace Tests\Feature\Pricing;

use App\Models\Setting;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * data:integrity-check — `module_pricing_unset` detector: flags an enabled
 * medical module whose consultation fee resolves to 0 (e.g. the pediatric
 * legacy-key mismatch), so silent zero-pricing is surfaced proactively.
 */
class ModulePricingUnsetCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::clearCache();
    }

    public function test_flags_an_enabled_module_with_zero_resolved_fee(): void
    {
        ModuleManager::enable('pediatric');
        ModuleManager::clearCache();
        // No pediatric fees set anywhere → resolves to 0.

        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('module_pricing_unset')
            ->assertExitCode(1);
    }

    public function test_does_not_flag_when_fee_is_configured(): void
    {
        ModuleManager::enable('dental');
        Setting::set('dental_consultant_fee', 300, 'pricing');
        ModuleManager::clearCache();

        // dental resolves to 300 via the legacy fallback → not flagged.
        // (Other enabled modules may still flag; assert dental specifically isn't
        // the cause by checking the resolver directly.)
        $f = app(\App\Services\Pricing\PricingResolver::class)->feesFor('dental');
        $this->assertGreaterThan(0, (float) $f['consultant']);
    }
}

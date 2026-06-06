<?php

namespace Tests\Feature\Pricing;

use App\Models\Doctor;
use App\Models\Setting;
use App\Services\ModuleManager;
use App\Services\Pricing\PricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADR-001 Phase 0 — characterization (safety net) for the pricing resolution
 * CONTRACT. Locks today's behaviour so any later unification phase that changes
 * a resolved price fails loudly:
 *   - resolution priority: follow-up → doctor override → consultant/specialist → base
 *   - the storage driver per module (settings vs module_settings) — Phase 2 will
 *     intentionally flip the legacy modules; updating this test IS that step's checklist.
 */
class PricingResolverCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private PricingResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::clearCache();
        $this->resolver = app(PricingResolver::class);
    }

    private function doctor(string $module, string $type = 'consultant', array $extra = []): Doctor
    {
        return Doctor::create(array_merge([
            'name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active',
            'module' => $module, 'doctor_type' => $type,
        ], $extra));
    }

    // ── Legacy `settings` driver (dental) ───────────────────────────────
    public function test_dental_resolves_from_global_settings_today(): void
    {
        Setting::set('dental_consultant_fee', 400, 'pricing');
        Setting::set('dental_specialist_fee', 300, 'pricing');
        Setting::set('followup_fee', 120, 'pricing');

        $this->assertEqualsWithDelta(400, $this->resolver->consultationFee($this->doctor('dental', 'consultant'), 'dental'), 0.01);
        $this->assertEqualsWithDelta(300, $this->resolver->consultationFee($this->doctor('dental', 'specialist'), 'dental'), 0.01);
        // Follow-up beats grade.
        $this->assertEqualsWithDelta(120, $this->resolver->consultationFee($this->doctor('dental', 'consultant'), 'dental', true), 0.01);
        // Doctor override beats grade.
        $override = $this->doctor('dental', 'consultant', ['dental_consultation_fee' => 555]);
        $this->assertEqualsWithDelta(555, $this->resolver->consultationFee($override, 'dental'), 0.01);
    }

    // ── module_settings driver (psychiatry) ─────────────────────────────
    public function test_psychiatry_resolves_from_module_settings_today(): void
    {
        ModuleManager::setSetting('psychiatry', 'consultant_fee', 350);
        ModuleManager::setSetting('psychiatry', 'specialist_fee', 250);
        ModuleManager::setSetting('psychiatry', 'followup_fee', 150);
        ModuleManager::clearCache();

        $this->assertEqualsWithDelta(350, $this->resolver->consultationFee($this->doctor('psychiatry', 'consultant'), 'psychiatry'), 0.01);
        $this->assertEqualsWithDelta(250, $this->resolver->consultationFee($this->doctor('psychiatry', 'specialist'), 'psychiatry'), 0.01);
        $this->assertEqualsWithDelta(150, $this->resolver->consultationFee($this->doctor('psychiatry', 'consultant'), 'psychiatry', true), 0.01);
        $override = $this->doctor('psychiatry', 'consultant', ['psychiatry_consultation_fee' => 600]);
        $this->assertEqualsWithDelta(600, $this->resolver->consultationFee($override, 'psychiatry'), 0.01);
    }

    /**
     * Driver-per-module map as it stands TODAY. Phase 2 (read-path flip) will
     * change the legacy modules to 'module'; this assertion failing then is the
     * expected, deliberate signal to update the migration in lockstep.
     */
    public function test_storage_driver_per_module_is_documented(): void
    {
        $ref = new \ReflectionMethod(PricingResolver::class, 'source');
        $ref->setAccessible(true);
        $driver = fn (string $m) => $ref->invoke($this->resolver, $m)['driver'];

        // Legacy global-Setting modules (today).
        foreach (['derma', 'cosmetic', 'dental', 'pediatric'] as $m) {
            $this->assertSame('settings', $driver($m), "$m should use the legacy settings driver today");
        }
        // module_settings modules.
        foreach (['obgyn', 'psychiatry', 'neurology'] as $m) {
            $this->assertSame('module', $driver($m), "$m should use the module_settings driver");
        }
    }

    public function test_pricing_audit_command_runs_read_only(): void
    {
        $this->artisan('pricing:audit')->assertExitCode(0);
        $this->artisan('pricing:audit --json')->assertExitCode(0);
    }
}

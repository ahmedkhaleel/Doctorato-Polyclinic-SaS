<?php

namespace Tests\Unit\Services;

use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModuleManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::clearCache();
    }

    /**
     * Helper: disable all medical modules except the ones we want for a test.
     * Since migrations seed derma=1 and pediatric=1, we need a clean slate.
     */
    private function disableAllModules(): void
    {
        DB::table('module_settings')
            ->where('key', 'enabled')
            ->update(['value' => '0']);
        ModuleManager::clearCache();
    }

    // ──────────────────────────────────────────────────────────────
    // isEnabled
    // ──────────────────────────────────────────────────────────────

    public function test_is_enabled_returns_true_for_enabled_module(): void
    {
        // derma is enabled by migration seeder
        $this->assertTrue(ModuleManager::isEnabled('derma'));
    }

    public function test_is_enabled_returns_false_for_disabled_module(): void
    {
        // dental is disabled by migration seeder
        $this->assertFalse(ModuleManager::isEnabled('dental'));
    }

    public function test_is_enabled_returns_false_for_unknown_module(): void
    {
        $this->assertFalse(ModuleManager::isEnabled('nonexistent'));
    }

    // ──────────────────────────────────────────────────────────────
    // No module is hardcoded as always-on (is_core removed)
    // ──────────────────────────────────────────────────────────────

    public function test_derma_is_not_hardcoded_core(): void
    {
        $this->assertFalse(
            ModuleManager::MODULES['derma']['is_core'],
            'derma should NOT be marked as is_core'
        );
    }

    public function test_no_module_is_marked_as_core(): void
    {
        foreach (ModuleManager::MODULES as $slug => $module) {
            $this->assertFalse($module['is_core'], "Module {$slug} should NOT be is_core");
        }
    }

    // ──────────────────────────────────────────────────────────────
    // getActiveMedicalModules
    // ──────────────────────────────────────────────────────────────

    public function test_get_active_medical_modules_returns_only_enabled_medical(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        $active = ModuleManager::getActiveMedicalModules();

        $this->assertContains('derma', $active);
        $this->assertContains('dental', $active);
        $this->assertNotContains('pediatric', $active);
    }

    public function test_get_active_medical_modules_excludes_non_medical(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::enable('hr');
        ModuleManager::clearCache();

        $active = ModuleManager::getActiveMedicalModules();

        $this->assertContains('derma', $active);
        $this->assertNotContains('hr', $active, 'HR is not a medical module');
    }

    public function test_get_active_medical_modules_empty_when_none_enabled(): void
    {
        $this->disableAllModules();

        $active = ModuleManager::getActiveMedicalModules();
        $this->assertEmpty($active);
    }

    // ──────────────────────────────────────────────────────────────
    // getDefaultModule
    // ──────────────────────────────────────────────────────────────

    public function test_get_default_module_returns_first_active_medical(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        // Only dental is active — it should be default
        $this->assertEquals('dental', ModuleManager::getDefaultModule());
    }

    public function test_get_default_module_returns_derma_when_it_is_first_active(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        // derma comes first in MEDICAL_MODULES array
        $this->assertEquals('derma', ModuleManager::getDefaultModule());
    }

    public function test_get_default_module_fallback_when_nothing_active(): void
    {
        $this->disableAllModules();

        // Nothing enabled — ultimate fallback is 'derma'
        $this->assertEquals('derma', ModuleManager::getDefaultModule());
    }

    // ──────────────────────────────────────────────────────────────
    // disable — last medical module protection
    // ──────────────────────────────────────────────────────────────

    public function test_cannot_disable_last_active_medical_module(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::clearCache();

        $result = ModuleManager::disable('derma');

        $this->assertFalse($result, 'Should not allow disabling the last active medical module');
        ModuleManager::clearCache('derma');
        $this->assertTrue(ModuleManager::isEnabled('derma'), 'derma should still be enabled');
    }

    public function test_can_disable_medical_module_when_another_is_active(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        $result = ModuleManager::disable('derma');

        $this->assertTrue($result, 'Should allow disabling derma when dental is still active');
        ModuleManager::clearCache('derma');
        $this->assertFalse(ModuleManager::isEnabled('derma'));
    }

    public function test_can_disable_non_medical_module_freely(): void
    {
        ModuleManager::enable('hr');
        ModuleManager::clearCache();

        $result = ModuleManager::disable('hr');
        $this->assertTrue($result, 'Non-medical modules can always be disabled');
    }

    public function test_cannot_disable_last_medical_even_with_non_medical_active(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::enable('hr');
        ModuleManager::enable('inventory');
        ModuleManager::clearCache();

        $result = ModuleManager::disable('derma');

        $this->assertFalse($result, 'hr and inventory are non-medical, derma is the only medical — cannot disable');
    }

    public function test_sequential_disable_stops_at_last_medical(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::enable('dental');
        ModuleManager::enable('pediatric');
        ModuleManager::clearCache();

        // Disable two — third should be blocked
        $this->assertTrue(ModuleManager::disable('derma'));
        ModuleManager::clearCache();

        $this->assertTrue(ModuleManager::disable('dental'));
        ModuleManager::clearCache();

        $this->assertFalse(
            ModuleManager::disable('pediatric'),
            'Cannot disable the last remaining medical module'
        );
    }

    // ──────────────────────────────────────────────────────────────
    // enable / disable for unknown modules
    // ──────────────────────────────────────────────────────────────

    public function test_enable_unknown_module_returns_false(): void
    {
        $this->assertFalse(ModuleManager::enable('nonexistent'));
    }

    public function test_disable_unknown_module_returns_false(): void
    {
        $this->assertFalse(ModuleManager::disable('nonexistent'));
    }

    // ──────────────────────────────────────────────────────────────
    // getActiveModules & getForFrontend
    // ──────────────────────────────────────────────────────────────

    public function test_get_active_modules_returns_only_enabled(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::enable('hr');
        ModuleManager::clearCache();

        $active = ModuleManager::getActiveModules();

        $this->assertArrayHasKey('derma', $active);
        $this->assertArrayHasKey('hr', $active);
        $this->assertArrayNotHasKey('dental', $active);
    }

    public function test_get_for_frontend_returns_all_modules_with_enabled_flag(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('derma');
        ModuleManager::clearCache();

        $frontend = ModuleManager::getForFrontend();

        $this->assertArrayHasKey('derma', $frontend);
        $this->assertArrayHasKey('dental', $frontend);
        $this->assertTrue($frontend['derma']['enabled']);
        $this->assertFalse($frontend['dental']['enabled']);
    }

    // ──────────────────────────────────────────────────────────────
    // MEDICAL_MODULES constant
    // ──────────────────────────────────────────────────────────────

    public function test_medical_modules_constant_contains_expected(): void
    {
        $this->assertEquals(['derma', 'dental', 'pediatric', 'obgyn'], ModuleManager::MEDICAL_MODULES);
    }

    // ──────────────────────────────────────────────────────────────
    // Any module can be the sole active medical module
    // ──────────────────────────────────────────────────────────────

    public function test_dental_can_be_sole_active_module(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        $this->assertEquals('dental', ModuleManager::getDefaultModule());
        $this->assertEquals(['dental'], ModuleManager::getActiveMedicalModules());
    }

    public function test_pediatric_can_be_sole_active_module(): void
    {
        $this->disableAllModules();

        ModuleManager::enable('pediatric');
        ModuleManager::clearCache();

        $this->assertEquals('pediatric', ModuleManager::getDefaultModule());
        $this->assertEquals(['pediatric'], ModuleManager::getActiveMedicalModules());
    }
}

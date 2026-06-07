<?php

namespace Tests\Feature\Physio;

use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PT-0 — the Physiotherapy module registers and integrates like the other
 * medical specialties: it's a medical module, ships disabled, can be enabled,
 * and its pricing + doctor fee fields exist.
 */
class PhysiotherapyFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_physiotherapy_is_a_registered_medical_module(): void
    {
        $this->assertContains('physiotherapy', ModuleManager::MEDICAL_MODULES);
        $this->assertArrayHasKey('physiotherapy', ModuleManager::getAllModules());
    }

    public function test_module_ships_disabled_and_can_be_enabled(): void
    {
        // Seeded by migration as disabled.
        $this->assertFalse(ModuleManager::isEnabled('physiotherapy'));

        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();

        $this->assertTrue(ModuleManager::isEnabled('physiotherapy'));
    }

    public function test_pricing_settings_and_doctor_fee_columns_exist(): void
    {
        $this->assertTrue(
            DB::table('module_settings')->where('module', 'physiotherapy')->where('key', 'session_fee')->exists(),
            'physiotherapy session_fee setting seeded'
        );
        $this->assertTrue(
            DB::table('module_settings')->where('module', 'physiotherapy')->where('key', 'consultation_fee')->exists()
        );

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('doctors', 'physiotherapy_consultation_fee'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('doctors', 'physiotherapy_session_fee'));
    }
}

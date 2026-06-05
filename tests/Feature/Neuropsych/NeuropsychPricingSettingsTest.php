<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * F2 — psychiatry & neurology now have module-level pricing/commission/
 * duration settings (mirroring obgyn), so the module settings page shows
 * them and booking/billing can read a module-level consultation fee.
 */
class NeuropsychPricingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_fee_is_seeded_for_both_modules(): void
    {
        ModuleManager::clearCache();
        $this->assertSame('300', (string) ModuleManager::getSetting('psychiatry', 'consultation_fee'));
        $this->assertSame('300', (string) ModuleManager::getSetting('neurology', 'consultation_fee'));
        $this->assertNotNull(ModuleManager::getSetting('psychiatry', 'session_fee'));
        $this->assertNotNull(ModuleManager::getSetting('neurology', 'procedure_fee'));
    }

    public function test_module_detail_page_exposes_pricing(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $admin = User::create(['name' => 'Admin', 'email' => 'f2-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        // getSettings() groups rows by `group`, each group an ordered list of
        // setting objects. The pricing group's first row is consultation_fee.
        $this->actingAs($admin)->get('/admin/settings/modules/psychiatry')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Settings/ModuleDetail')
                ->has('module.settings.pricing')
                ->where('module.settings.pricing.0.key', 'consultation_fee')
                ->where('module.settings.pricing.0.value', '300')
            );
    }
}

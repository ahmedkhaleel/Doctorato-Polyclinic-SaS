<?php

namespace Tests\Feature\Pricing;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\Pricing\PricingResolver;
use App\Services\Pricing\PricingSettingsMirror;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADR-001 Phase 3 — saving a legacy fee in the admin settings editor must
 * dual-write: the legacy Setting is updated AND mirrored into module_settings,
 * so the resolver (which prefers a positive module_settings value) reflects the
 * new fee. Both stores stay in sync; the legacy key remains as a rollback path.
 */
class PricingDualWriteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::clearCache();

        $role = Role::firstOrCreate(['name' => 'admin'], [
            'display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true,
        ]);
        $role->update(['permissions' => ['*']]);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'pricing-dual@test.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_mirror_service_syncs_legacy_to_module_settings(): void
    {
        Setting::set('dental_consultant_fee', 480, 'pricing');
        Setting::set('dental_specialist_fee', 360, 'pricing');

        app(PricingSettingsMirror::class)->mirror();
        ModuleManager::clearCache();

        $this->assertEqualsWithDelta(480, (float) ModuleManager::getSetting('dental', 'consultant_fee'), 0.01);
        $this->assertEqualsWithDelta(360, (float) ModuleManager::getSetting('dental', 'specialist_fee'), 0.01);

        // Resolver reflects the mirrored value.
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'dental', 'doctor_type' => 'consultant']);
        $this->assertEqualsWithDelta(480, app(PricingResolver::class)->consultationFee($doctor, 'dental'), 0.01);
    }

    public function test_touches_pricing_detects_legacy_fee_keys(): void
    {
        $mirror = app(PricingSettingsMirror::class);
        $this->assertTrue($mirror->touchesPricing(['dental_consultant_fee', 'clinic_name']));
        $this->assertFalse($mirror->touchesPricing(['clinic_name', 'clinic_phone']));
    }

    public function test_settings_update_endpoint_dual_writes_when_fee_changes(): void
    {
        // Drive the actual admin settings update path with a dental fee change.
        $this->actingAs($this->admin)->post('/admin/settings', [
            'dental_consultant_fee' => 520,
        ])->assertRedirect();

        ModuleManager::clearCache();

        // Legacy written…
        $this->assertEqualsWithDelta(520, (float) Setting::get('dental_consultant_fee'), 0.01);
        // …and mirrored to module_settings.
        $this->assertEqualsWithDelta(520, (float) ModuleManager::getSetting('dental', 'consultant_fee'), 0.01);
    }
}

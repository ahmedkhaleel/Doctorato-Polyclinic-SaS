<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Setting;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::clearCache();
    }

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    public function test_disabled_behaves_globally(): void
    {
        config(['branches.enabled' => false]);
        Setting::set('clinic_name', 'Main Clinic');
        $this->assertSame('Main Clinic', Setting::get('clinic_name'));
    }

    public function test_branch_override_with_global_fallback(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        Setting::set('clinic_name', 'Global Clinic');               // global (branch 0)
        Setting::setForBranch($b2->id, 'clinic_name', 'Maadi Clinic'); // override for b2

        Setting::clearCache();

        // Branch 2 sees its override
        $this->ctx()->set($b2->id);
        $this->assertSame('Maadi Clinic', Setting::get('clinic_name'));

        // Branch 1 (no override) falls back to global
        Setting::clearCache();
        $this->ctx()->set(1);
        $this->assertSame('Global Clinic', Setting::get('clinic_name'));

        // A key with no override anywhere → global value for every branch
        Setting::set('phone_1', '0100');
        Setting::clearCache();
        $this->ctx()->set($b2->id);
        $this->assertSame('0100', Setting::get('phone_1'));
    }

    public function test_clear_branch_override_restores_global(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        Setting::set('working_hours', '9-5');
        Setting::setForBranch($b2->id, 'working_hours', '10-8');

        Setting::clearBranchOverride($b2->id, 'working_hours');
        Setting::clearCache();

        $this->ctx()->set($b2->id);
        $this->assertSame('9-5', Setting::get('working_hours'));
    }
}

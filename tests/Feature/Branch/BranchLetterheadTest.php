<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Setting;
use App\Services\Branch\BranchLetterhead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchLetterheadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::clearCache();
    }

    public function test_uses_branch_identity_when_available(): void
    {
        config(['branches.enabled' => true]);
        Setting::set('site_name', 'Main Clinic');
        Setting::set('address', 'HQ Street');

        $b2 = Branch::create([
            'name_ar' => 'فرع المعادي', 'name_en' => 'Maadi Branch', 'code' => 'MAADI',
            'address' => 'Maadi, 9 St', 'phone' => '0223334444',
        ]);

        $clinic = BranchLetterhead::for($b2->id);
        $this->assertSame('Maadi Branch', $clinic['name']);
        $this->assertSame('Maadi, 9 St', $clinic['address']);
        $this->assertSame('0223334444', $clinic['phone']);
    }

    public function test_falls_back_to_global_for_main_branch_without_own_fields(): void
    {
        config(['branches.enabled' => true]);
        Setting::set('site_name', 'Main Clinic');
        Setting::set('address', 'HQ Street');
        Setting::clearCache();

        // Main branch (id 1) has no address/phone of its own → global settings.
        $clinic = BranchLetterhead::for(1);
        $this->assertSame('HQ Street', $clinic['address']);
    }

    public function test_null_branch_uses_global(): void
    {
        Setting::set('site_name', 'Global Clinic');
        Setting::clearCache();
        $clinic = BranchLetterhead::for(null);
        $this->assertSame('Global Clinic', $clinic['name']);
    }
}

<?php

namespace Tests\Feature\Secretary;

use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every specialty front desk in the secretary portal must render for a
 * secretary once the module is enabled — administrative only, no clinical leak.
 */
class SecretaryFrontDeskAllSpecialtiesTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'secretary'], [
            'display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true,
        ]);
        $this->secretary = User::create([
            'name' => 'Front Desk', 'email' => 'frontdesk-all@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_all_specialty_front_desks_render(): void
    {
        $pages = [
            'derma' => '/secretary/derma/overview',
            'telemedicine' => '/secretary/telemedicine/overview',
            'psychiatry' => '/secretary/psychiatry/overview',
            'neurology' => '/secretary/neurology/overview',
            'obgyn' => '/secretary/obgyn/pregnancies',
            'pediatric' => '/secretary/pediatric/patients',
            'dental' => '/secretary/dental/lab-orders',
        ];

        foreach ($pages as $module => $url) {
            ModuleManager::enable($module);
            ModuleManager::flushStaticCache();
            $this->actingAs($this->secretary)->get($url)->assertOk();
        }

        // Dental treatment-plans list too (other dental front-desk view).
        ModuleManager::enable('dental');
        ModuleManager::flushStaticCache();
        $this->actingAs($this->secretary)->get('/secretary/dental/treatment-plans')->assertOk();
    }
}

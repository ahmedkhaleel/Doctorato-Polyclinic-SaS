<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ModuleManager;
use Database\Seeders\FullDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The one-stop demo: a login for every role AND every specialty, with all
 * medical modules enabled so the demo is navigable end to end.
 */
class FullDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_demo_seeds_all_role_and_specialty_logins(): void
    {
        $this->seed(FullDemoSeeder::class);
        ModuleManager::flushStaticCache();

        // Every medical module is enabled (navigable demo).
        foreach (ModuleManager::MEDICAL_MODULES as $m) {
            $this->assertTrue(ModuleManager::isEnabled($m), "module {$m} enabled");
        }

        // Role logins.
        foreach (['demo.admin', 'demo.secretary', 'demo.patient', 'demo.doctor'] as $u) {
            $this->assertNotNull(User::where('email', "{$u}@doctorato.net")->first(), "{$u} login");
        }

        // One doctor login per specialty.
        foreach (ModuleManager::MEDICAL_MODULES as $m) {
            $this->assertNotNull(User::where('email', "demo.{$m}@doctorato.net")->first(), "demo.{$m} login");
        }
    }
}

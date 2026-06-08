<?php

namespace Tests\Feature\Physio;

use App\Models\PhysioPackage;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** G-A — admin manages the physio package catalog (CRUD + toggle). */
class PhysiotherapyAdminPackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();
        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();
    }

    private function admin(array $perms): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name_en' => 'SA', 'display_name_ar' => 'SA', 'permissions' => $perms, 'is_system' => true]);
        $role->update(['permissions' => $perms]);

        return User::create(['name' => 'SA', 'email' => 'pkgadm@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    public function test_admin_can_open_create_and_toggle_packages(): void
    {
        $user = $this->admin(['*']);

        $this->actingAs($user)->get('/admin/physiotherapy/packages')->assertOk();

        $this->actingAs($user)->post('/admin/physiotherapy/packages', [
            'name_en' => '20-Session Pro', 'name_ar' => 'باقة 20 جلسة', 'total_sessions' => 20, 'price' => 3200, 'validity_days' => 120,
        ])->assertRedirect();

        $p = PhysioPackage::where('name_en', '20-Session Pro')->firstOrFail();
        $this->assertSame(20, (int) $p->total_sessions);
        $this->assertTrue($p->is_active);

        $this->actingAs($user)->post("/admin/physiotherapy/packages/{$p->id}/toggle")->assertRedirect();
        $this->assertFalse($p->fresh()->is_active);

        $this->actingAs($user)->post("/admin/physiotherapy/packages/{$p->id}", [
            'name_en' => '20-Session Pro', 'name_ar' => 'باقة 20 جلسة', 'total_sessions' => 24, 'price' => 3600,
        ])->assertRedirect();
        $this->assertSame(24, (int) $p->fresh()->total_sessions);
    }
}

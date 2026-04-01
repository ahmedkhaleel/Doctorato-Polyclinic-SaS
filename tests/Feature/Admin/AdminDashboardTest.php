<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Admin',
                'display_name_ar' => 'مدير',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    // ─── Dashboard Access ──────────────────────────────

    public function test_admin_can_view_dashboard(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_returns_statistics(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);

        // The dashboard Inertia page should receive these props
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('financial')
            ->has('clinic')
            ->has('alerts')
        );
    }

    // ─── Auth Guard ────────────────────────────────────

    public function test_unauthenticated_user_redirected_from_dashboard(): void
    {
        $response = $this->get('/admin');

        // Should redirect to login
        $response->assertRedirect();
    }
}

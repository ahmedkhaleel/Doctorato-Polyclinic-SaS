<?php

namespace Tests\Feature\Webmaster;

use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebmasterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $webmaster;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'webmaster'],
            [
                'display_name_en' => 'Webmaster',
                'display_name_ar' => 'مدير الموقع',
                'permissions' => [
                    'services.view', 'services.create', 'services.update', 'services.delete',
                    'service_categories.view', 'service_categories.create',
                ],
                'is_system' => false,
            ]
        );

        $this->webmaster = User::create([
            'name' => 'Webmaster User',
            'email' => 'webmaster@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->category = ServiceCategory::create([
            'name_ar' => 'تصنيف تجريبي',
            'name_en' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    // ─── Index ─────────────────────────────────────────

    public function test_webmaster_can_view_services_index(): void
    {
        $this->actingAs($this->webmaster);

        $response = $this->get('/webmaster/services');
        $response->assertStatus(200);
    }

    // ─── Store ─────────────────────────────────────────

    public function test_webmaster_can_create_service(): void
    {
        $this->actingAs($this->webmaster);

        $response = $this->post('/webmaster/services', [
            'category_id' => $this->category->id,
            'name_ar' => 'خدمة جديدة',
            'name_en' => 'New Service',
            'status' => 'active',
            'show_on_website' => true,
            'show_on_home' => false,
            'bookable' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Service created successfully.');

        $this->assertDatabaseHas('services', [
            'name_en' => 'New Service',
            'slug' => 'new-service',
            'status' => 'active',
        ]);
    }

    // ─── Validation ────────────────────────────────────

    public function test_service_requires_name(): void
    {
        $this->actingAs($this->webmaster);

        $response = $this->post('/webmaster/services', [
            'category_id' => $this->category->id,
            'status' => 'active',
            'show_on_website' => true,
            'bookable' => false,
        ]);

        $response->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    // ─── Slug Generation ───────────────────────────────

    public function test_service_auto_generates_slug(): void
    {
        $this->actingAs($this->webmaster);

        $response = $this->post('/webmaster/services', [
            'category_id' => $this->category->id,
            'name_ar' => 'تقشير كيميائي',
            'name_en' => 'Chemical Peel',
            'status' => 'active',
            'show_on_website' => true,
            'show_on_home' => false,
            'bookable' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('services', [
            'name_en' => 'Chemical Peel',
            'slug' => 'chemical-peel',
        ]);
    }

    public function test_duplicate_slug_gets_suffix(): void
    {
        // Create the first service to occupy the slug
        Service::create([
            'category_id' => $this->category->id,
            'name_ar' => 'ليزر',
            'name_en' => 'Laser Treatment',
            'slug' => 'laser-treatment',
            'status' => 'active',
            'show_on_website' => true,
            'bookable' => true,
        ]);

        $this->actingAs($this->webmaster);

        $response = $this->post('/webmaster/services', [
            'category_id' => $this->category->id,
            'name_ar' => 'ليزر جديد',
            'name_en' => 'Laser Treatment',
            'status' => 'active',
            'show_on_website' => true,
            'show_on_home' => false,
            'bookable' => true,
        ]);

        $response->assertRedirect();

        // The slug should have a suffix since "laser-treatment" already exists
        $this->assertDatabaseHas('services', [
            'name_en' => 'Laser Treatment',
            'slug' => 'laser-treatment-1',
        ]);
    }
}

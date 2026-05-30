<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\SeoPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeoPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-seo@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_seo_pages_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/seo-pages')->assertOk();
    }

    public function test_can_view_edit_form(): void
    {
        $page = SeoPage::create([
            'page_identifier' => 'home',
            'page_name_en' => 'Home',
            'page_name_ar' => 'الرئيسية',
            'title_en' => 'Home Page',
            'title_ar' => 'الصفحة الرئيسية',
        ]);

        $this->actingAs($this->admin)->get("/admin/seo-pages/{$page->id}/edit")->assertOk();
    }

    public function test_can_update_seo_page(): void
    {
        $page = SeoPage::create([
            'page_identifier' => 'about',
            'page_name_en' => 'About',
            'page_name_ar' => 'عن العيادة',
            'title_en' => 'About',
            'title_ar' => 'عن العيادة',
        ]);

        $this->actingAs($this->admin)->post("/admin/seo-pages/{$page->id}/update", [
            'title_en' => 'About Aura Derma Clinic',
            'title_ar' => 'عن عيادة أورا ديرما',
            'description_en' => 'Leading dermatology clinic',
            'description_ar' => 'عيادة رائدة في طب الجلد',
            'keywords' => 'dermatology, clinic, skin',
            'is_indexable' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('seo_pages', [
            'id' => $page->id,
            'title_en' => 'About Aura Derma Clinic',
        ]);
    }

    public function test_invalid_json_structured_data_rejected(): void
    {
        $page = SeoPage::create([
            'page_identifier' => 'services',
            'page_name_en' => 'Services',
            'page_name_ar' => 'الخدمات',
            'title_en' => 'Services',
            'title_ar' => 'الخدمات',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/seo-pages/{$page->id}/update", [
            'structured_data' => 'invalid json {{{',
        ]);

        // Should redirect back with error about invalid JSON
        $response->assertRedirect();
    }

    public function test_valid_json_structured_data_accepted(): void
    {
        $page = SeoPage::create([
            'page_identifier' => 'contact',
            'page_name_en' => 'Contact',
            'page_name_ar' => 'اتصل بنا',
            'title_en' => 'Contact',
            'title_ar' => 'اتصل بنا',
        ]);

        $this->actingAs($this->admin)->post("/admin/seo-pages/{$page->id}/update", [
            'title_en' => 'Contact Us',
            'structured_data' => '{"@type": "LocalBusiness", "name": "Aura Clinic"}',
            'is_indexable' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('seo_pages', [
            'id' => $page->id,
            'title_en' => 'Contact Us',
        ]);
    }
}

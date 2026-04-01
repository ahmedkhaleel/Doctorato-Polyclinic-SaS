<?php

namespace Tests\Feature\Admin;

use App\Models\PostCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostCategoryTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-postcat@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_post_categories(): void
    {
        $this->actingAs($this->admin)->get('/admin/post-categories')->assertOk();
    }

    public function test_can_create_post_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/post-categories', [
            'name_ar' => 'نصائح طبية',
            'name_en' => 'Medical Tips',
        ])->assertRedirect();

        $this->assertDatabaseHas('post_categories', ['name_en' => 'Medical Tips']);
    }

    public function test_post_category_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/post-categories', [])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_post_category(): void
    {
        $cat = PostCategory::create(['name_ar' => 'قديم', 'name_en' => 'Old', 'slug' => 'old']);

        $this->actingAs($this->admin)->put("/admin/post-categories/{$cat->id}", [
            'name_ar' => 'محدث',
            'name_en' => 'Updated',
        ])->assertRedirect();

        $this->assertDatabaseHas('post_categories', ['id' => $cat->id, 'name_en' => 'Updated']);
    }

    public function test_can_delete_post_category(): void
    {
        $cat = PostCategory::create(['name_ar' => 'حذف', 'name_en' => 'Delete', 'slug' => 'delete']);

        $this->actingAs($this->admin)->delete("/admin/post-categories/{$cat->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('post_categories', ['id' => $cat->id]);
    }
}

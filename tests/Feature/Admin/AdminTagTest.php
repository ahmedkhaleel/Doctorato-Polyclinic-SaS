<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-tag@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_tags_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/tags')->assertOk();
    }

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/tags/create')->assertOk();
    }

    public function test_can_create_tag(): void
    {
        $this->actingAs($this->admin)->post('/admin/tags', [
            'name_ar' => 'علاج البشرة',
            'name_en' => 'Skin Treatment',
        ])->assertRedirect();

        $this->assertDatabaseHas('tags', [
            'name_en' => 'Skin Treatment',
            'slug' => 'skin-treatment',
        ]);
    }

    public function test_tag_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/tags', [])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_tag(): void
    {
        $tag = Tag::create([
            'name_ar' => 'قديم', 'name_en' => 'Old Tag', 'slug' => 'old-tag',
        ]);

        $this->actingAs($this->admin)->put("/admin/tags/{$tag->id}", [
            'name_ar' => 'جديد',
            'name_en' => 'New Tag',
        ])->assertRedirect();

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'slug' => 'new-tag']);
    }

    public function test_can_delete_tag(): void
    {
        $tag = Tag::create([
            'name_ar' => 'للحذف', 'name_en' => 'Delete Me', 'slug' => 'delete-me',
        ]);

        $this->actingAs($this->admin)->delete("/admin/tags/{$tag->id}")->assertRedirect();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_slug_auto_generated_from_name_en(): void
    {
        $this->actingAs($this->admin)->post('/admin/tags', [
            'name_ar' => 'حب الشباب',
            'name_en' => 'Acne Treatment Tips',
        ])->assertRedirect();

        $this->assertDatabaseHas('tags', ['slug' => 'acne-treatment-tips']);
    }
}

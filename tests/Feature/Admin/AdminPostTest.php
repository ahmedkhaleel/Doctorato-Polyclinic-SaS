<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Doctor $doctor;
    private PostCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-post@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        // Posts author_id references doctors table
        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور مقال', 'name_en' => 'Post Doctor',
            'user_id' => $this->admin->id, 'status' => 'active',
        ]);

        $this->category = PostCategory::create([
            'name_ar' => 'مقالات', 'name_en' => 'Articles', 'slug' => 'articles',
        ]);
    }

    public function test_can_view_posts_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/posts')->assertOk();
    }

    public function test_can_view_create_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/posts/create')->assertOk();
    }

    public function test_can_create_post(): void
    {
        $this->actingAs($this->admin)->post('/admin/posts', [
            'title_ar' => 'مقال جديد',
            'title_en' => 'New Post',
            'content_ar' => '<p>محتوى عربي</p>',
            'content_en' => '<p>English content</p>',
            'category_id' => $this->category->id,
            'status' => 'published',
            'author_id' => $this->doctor->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('posts', ['title_en' => 'New Post']);
    }

    public function test_post_requires_title(): void
    {
        $this->actingAs($this->admin)->post('/admin/posts', [
            'content_ar' => 'محتوى',
            'content_en' => 'Content',
            'status' => 'draft',
        ])->assertSessionHasErrors(['title_ar', 'title_en']);
    }

    public function test_post_requires_content(): void
    {
        $this->actingAs($this->admin)->post('/admin/posts', [
            'title_ar' => 'عنوان',
            'title_en' => 'Title',
            'status' => 'draft',
        ])->assertSessionHasErrors(['content_ar', 'content_en']);
    }

    public function test_can_update_post(): void
    {
        $post = Post::create([
            'title_ar' => 'قديم', 'title_en' => 'Old Post',
            'content_ar' => 'محتوى', 'content_en' => 'Content',
            'slug' => 'old-post', 'status' => 'draft',
            'author_id' => $this->doctor->id,
        ]);

        $this->actingAs($this->admin)->put("/admin/posts/{$post->id}", [
            'title_ar' => 'محدث',
            'title_en' => 'Updated Post',
            'content_ar' => 'محتوى محدث',
            'content_en' => 'Updated content',
            'status' => 'published',
            'author_id' => $this->doctor->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title_en' => 'Updated Post']);
    }

    public function test_can_delete_post(): void
    {
        $post = Post::create([
            'title_ar' => 'حذف', 'title_en' => 'Delete Me',
            'content_ar' => 'محتوى', 'content_en' => 'Content',
            'slug' => 'delete-me', 'status' => 'draft',
            'author_id' => $this->doctor->id,
        ]);

        $this->actingAs($this->admin)->delete("/admin/posts/{$post->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_post_auto_generates_slug(): void
    {
        $this->actingAs($this->admin)->post('/admin/posts', [
            'title_ar' => 'عنوان',
            'title_en' => 'Auto Slug Post',
            'content_ar' => 'محتوى',
            'content_en' => 'Content',
            'status' => 'draft',
            'author_id' => $this->doctor->id,
        ])->assertRedirect();

        $post = Post::where('title_en', 'Auto Slug Post')->first();
        $this->assertNotNull($post);
        $this->assertNotEmpty($post->slug);
    }
}

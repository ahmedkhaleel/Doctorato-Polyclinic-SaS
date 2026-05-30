<?php

namespace Tests\Feature\Admin;

use App\Models\Gallery;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGalleryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-gallery@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_gallery_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/gallery')->assertOk();
    }

    public function test_can_create_gallery_item(): void
    {
        $this->actingAs($this->admin)->post('/admin/gallery', [
            'category' => 'Dermatology',
            'caption_ar' => 'صورة جديدة',
            'caption_en' => 'New Image',
            'is_visible' => true,
            'image_path' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertRedirect();

        $this->assertDatabaseHas('gallery', ['category' => 'Dermatology']);
    }

    public function test_gallery_requires_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/gallery', [
            'image_path' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertSessionHasErrors('category');
    }

    public function test_gallery_rejects_invalid_file_type(): void
    {
        $this->actingAs($this->admin)->post('/admin/gallery', [
            'category' => 'Test',
            'image_path' => UploadedFile::fake()->create('malware.exe', 1024),
        ])->assertSessionHasErrors('image_path');
    }

    public function test_can_update_gallery_item(): void
    {
        $gallery = Gallery::create([
            'category' => 'Old Category',
            'caption_ar' => 'قديم',
            'caption_en' => 'Old',
            'is_visible' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/gallery/{$gallery->id}/update", [
            'category' => 'Updated Category',
            'caption_ar' => 'محدث',
            'caption_en' => 'Updated',
            'is_visible' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('gallery', ['id' => $gallery->id, 'category' => 'Updated Category']);
    }

    public function test_can_delete_gallery_item(): void
    {
        $gallery = Gallery::create([
            'category' => 'Delete Me',
            'caption_ar' => 'حذف',
            'caption_en' => 'Delete',
        ]);

        $this->actingAs($this->admin)->post("/admin/gallery/{$gallery->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseMissing('gallery', ['id' => $gallery->id]);
    }

    public function test_can_create_before_after_item(): void
    {
        $this->actingAs($this->admin)->post('/admin/gallery', [
            'category' => 'Before After',
            'is_before_after' => true,
            'before_image' => UploadedFile::fake()->image('before.jpg', 800, 600),
            'after_image' => UploadedFile::fake()->image('after.jpg', 800, 600),
        ])->assertRedirect();

        $this->assertDatabaseHas('gallery', ['category' => 'Before After', 'is_before_after' => true]);
    }
}

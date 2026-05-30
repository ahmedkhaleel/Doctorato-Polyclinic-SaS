<?php

namespace Tests\Feature\Admin;

use App\Models\HeroSlide;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSliderTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-slider@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_slider_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/slider')->assertOk();
    }

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/slider/create')->assertOk();
    }

    public function test_can_create_slide(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post('/admin/slider', [
            'title_ar' => 'عنوان',
            'title_en' => 'Title',
            'subtitle_ar' => 'عنوان فرعي',
            'subtitle_en' => 'Subtitle',
            'image' => UploadedFile::fake()->image('slide.jpg'),
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('hero_slides', ['title_en' => 'Title']);
    }

    public function test_slide_requires_image(): void
    {
        $this->actingAs($this->admin)->post('/admin/slider', [
            'title_ar' => 'عنوان',
            'title_en' => 'Title',
        ])->assertSessionHasErrors('image');
    }

    public function test_rejects_non_image_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post('/admin/slider', [
            'title_en' => 'Title',
            'title_ar' => 'عنوان',
            'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');
    }

    public function test_can_update_slide(): void
    {
        Storage::fake('public');

        $slide = HeroSlide::create([
            'title_ar' => 'قديم', 'title_en' => 'Old',
            'image' => 'slides/old.jpg', 'is_active' => true, 'display_order' => 1,
        ]);

        $this->actingAs($this->admin)->post("/admin/slider/{$slide->id}/update", [
            'title_ar' => 'جديد',
            'title_en' => 'Updated',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('hero_slides', ['id' => $slide->id, 'title_en' => 'Updated']);
    }

    public function test_can_delete_slide(): void
    {
        Storage::fake('public');

        $slide = HeroSlide::create([
            'title_ar' => 'حذف', 'title_en' => 'Delete',
            'image' => 'slides/delete.jpg', 'is_active' => true, 'display_order' => 1,
        ]);

        $this->actingAs($this->admin)->post("/admin/slider/{$slide->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('hero_slides', ['id' => $slide->id]);
    }

    public function test_display_order_auto_set(): void
    {
        Storage::fake('public');

        HeroSlide::create([
            'title_ar' => 'أول', 'title_en' => 'First',
            'image' => 'slides/1.jpg', 'is_active' => true, 'display_order' => 5,
        ]);

        $this->actingAs($this->admin)->post('/admin/slider', [
            'title_ar' => 'ثاني',
            'title_en' => 'Second',
            'image' => UploadedFile::fake()->image('slide2.jpg'),
            'is_active' => true,
        ])->assertRedirect();

        $second = HeroSlide::where('title_en', 'Second')->first();
        $this->assertGreaterThanOrEqual(6, $second->display_order);
    }
}

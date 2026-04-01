<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTestimonialTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-test@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_testimonials_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/testimonials')->assertOk();
    }

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/testimonials/create')->assertOk();
    }

    public function test_can_create_testimonial(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post('/admin/testimonials', [
            'client_name_ar' => 'أحمد',
            'client_name_en' => 'Ahmed',
            'rating' => 5,
            'review_ar' => 'خدمة ممتازة',
            'review_en' => 'Excellent service',
            'status' => 'published',
            'photo' => UploadedFile::fake()->image('client.jpg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('testimonials', [
            'client_name_en' => 'Ahmed',
            'rating' => 5,
        ]);
    }

    public function test_testimonial_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/testimonials', [])
            ->assertSessionHasErrors(['client_name_ar', 'client_name_en', 'rating', 'review_ar', 'review_en', 'status']);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $this->actingAs($this->admin)->post('/admin/testimonials', [
            'client_name_ar' => 'تست',
            'client_name_en' => 'Test',
            'rating' => 6,
            'review_ar' => 'مراجعة',
            'review_en' => 'Review',
            'status' => 'published',
        ])->assertSessionHasErrors('rating');
    }

    public function test_can_update_testimonial(): void
    {
        $testimonial = Testimonial::create([
            'client_name_ar' => 'قديم', 'client_name_en' => 'Old',
            'rating' => 3, 'review_ar' => 'مراجعة', 'review_en' => 'Review',
            'status' => 'published',
        ]);

        $this->actingAs($this->admin)->put("/admin/testimonials/{$testimonial->id}", [
            'client_name_ar' => 'جديد',
            'client_name_en' => 'Updated',
            'rating' => 5,
            'review_ar' => 'مراجعة محدثة',
            'review_en' => 'Updated review',
            'status' => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('testimonials', ['id' => $testimonial->id, 'rating' => 5]);
    }

    public function test_can_delete_testimonial(): void
    {
        $testimonial = Testimonial::create([
            'client_name_ar' => 'حذف', 'client_name_en' => 'Delete',
            'rating' => 4, 'review_ar' => 'مراجعة', 'review_en' => 'Review',
            'status' => 'published',
        ]);

        $this->actingAs($this->admin)->delete("/admin/testimonials/{$testimonial->id}")->assertRedirect();
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }
}

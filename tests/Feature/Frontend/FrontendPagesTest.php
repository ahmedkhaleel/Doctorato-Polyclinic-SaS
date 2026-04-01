<?php

namespace Tests\Feature\Frontend;

use App\Models\Doctor;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads(): void
    {
        $response = $this->get('/en');
        $response->assertStatus(200);
    }

    public function test_homepage_loads_arabic(): void
    {
        $response = $this->get('/ar');
        $response->assertStatus(200);
    }

    public function test_about_page_loads(): void
    {
        $response = $this->get('/en/about');
        $response->assertStatus(200);
    }

    public function test_contact_page_loads(): void
    {
        $response = $this->get('/en/contact');
        $response->assertStatus(200);
    }

    public function test_contact_form_submission(): void
    {
        $response = $this->post('/en/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '01234567890',
            'message' => 'Test message for the clinic.',
        ]);

        $response->assertRedirect();
    }

    public function test_faq_page_loads(): void
    {
        $response = $this->get('/en/faq');
        $response->assertStatus(200);
    }

    public function test_gallery_page_loads(): void
    {
        $response = $this->get('/en/gallery');
        $response->assertStatus(200);
    }

    public function test_offers_page_loads(): void
    {
        $this->markTestSkipped('Offers route not yet implemented.');
    }

    public function test_services_page_loads(): void
    {
        $response = $this->get('/en/services');
        $response->assertStatus(200);
    }

    public function test_service_detail_page_loads(): void
    {
        $category = ServiceCategory::create([
            'name_ar' => 'تصنيف',
            'name_en' => 'Category',
            'slug' => 'category',
        ]);

        $service = Service::create([
            'category_id' => $category->id,
            'name_ar' => 'خدمة',
            'name_en' => 'Test Service',
            'slug' => 'test-service',
            'status' => 'active',
            'show_on_website' => true,
            'bookable' => true,
            'price' => 500,
        ]);

        $response = $this->get('/en/services/test-service');
        $response->assertStatus(200);
    }

    public function test_blog_page_loads(): void
    {
        $response = $this->get('/en/blog');
        $response->assertStatus(200);
    }

    public function test_booking_page_loads(): void
    {
        $response = $this->get('/en/booking');
        $response->assertStatus(200);
    }

    public function test_root_redirects(): void
    {
        $response = $this->get('/');
        $response->assertRedirect();
    }
}

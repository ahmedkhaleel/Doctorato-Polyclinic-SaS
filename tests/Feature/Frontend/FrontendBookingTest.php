<?php

namespace Tests\Feature\Frontend;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendBookingTest extends TestCase
{
    use RefreshDatabase;

    protected Doctor $doctor;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور تست',
            'name_en' => 'Dr. Test',
            'status' => 'active',
        ]);

        $category = ServiceCategory::create([
            'name_ar' => 'تصنيف',
            'name_en' => 'Category',
            'slug' => 'cat',
        ]);

        $this->service = Service::create([
            'category_id' => $category->id,
            'name_ar' => 'خدمة',
            'name_en' => 'Service',
            'slug' => 'service',
            'status' => 'active',
            'bookable' => true,
            'show_on_website' => true,
            'price' => 500,
        ]);
    }

    public function test_booking_page_loads_with_services_and_doctors(): void
    {
        $response = $this->get('/en/booking');
        $response->assertStatus(200);
    }

    public function test_can_submit_service_booking(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'John Doe',
            'phone' => '01234567890',
            'email' => 'john@example.com',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'preferred_date' => now()->addDays(3)->format('Y-m-d'),
            'preferred_time' => '10:00',
            'notes' => 'I need help',
            'privacy_consent' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'full_name' => 'John Doe',
            'phone' => '01234567890',
            'booking_type' => 'service',
            'source' => 'website',
            'status' => 'unconfirmed',
        ]);
    }

    public function test_can_submit_dermatology_consultation_booking(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Jane Doe',
            'phone' => '01111111111',
            'booking_type' => 'dermatology_consultation',
            'doctor_id' => $this->doctor->id,
            'preferred_date' => now()->addDays(2)->format('Y-m-d'),
            'privacy_consent' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'full_name' => 'Jane Doe',
            'booking_type' => 'dermatology_consultation',
            'source' => 'website',
            'status' => 'unconfirmed',
            'service_id' => null,
        ]);
    }

    public function test_can_submit_cosmetic_consultation_booking(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Sara Ahmed',
            'phone' => '01222222222',
            'booking_type' => 'cosmetic_consultation',
            'doctor_id' => $this->doctor->id,
            'privacy_consent' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'booking_type' => 'cosmetic_consultation',
            'status' => 'unconfirmed',
        ]);
    }

    public function test_booking_requires_full_name(): void
    {
        $response = $this->post('/en/booking', [
            'phone' => '01234567890',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'privacy_consent' => true,
        ]);

        $response->assertSessionHasErrors('full_name');
    }

    public function test_booking_requires_phone(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Test',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'privacy_consent' => true,
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_booking_requires_booking_type(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Test',
            'phone' => '01234567890',
            'service_id' => $this->service->id,
            'privacy_consent' => true,
        ]);

        $response->assertSessionHasErrors('booking_type');
    }

    public function test_booking_requires_privacy_consent(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Test',
            'phone' => '01234567890',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
        ]);

        $response->assertSessionHasErrors('privacy_consent');
    }

    public function test_service_booking_requires_service_id(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Test',
            'phone' => '01234567890',
            'booking_type' => 'service',
            'privacy_consent' => true,
        ]);

        $response->assertSessionHasErrors('service_id');
    }

    public function test_consultation_booking_does_not_require_service_id(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Test',
            'phone' => '01234567890',
            'booking_type' => 'dermatology_consultation',
            'privacy_consent' => true,
        ]);

        $response->assertSessionDoesntHaveErrors('service_id');
    }

    public function test_invalid_booking_type_rejected(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Test',
            'phone' => '01234567890',
            'booking_type' => 'invalid',
            'privacy_consent' => true,
        ]);

        $response->assertSessionHasErrors('booking_type');
    }

    public function test_honeypot_field_blocks_spam(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Spammer',
            'phone' => '01234567890',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'privacy_consent' => true,
            '_honeypot' => 'I am a bot filling hidden field',
        ]);

        $response->assertSessionHasErrors('_honeypot');
    }

    public function test_booking_generates_unique_number(): void
    {
        $this->post('/en/booking', [
            'full_name' => 'First',
            'phone' => '01111111111',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'privacy_consent' => true,
        ]);

        $this->post('/en/booking', [
            'full_name' => 'Second',
            'phone' => '02222222222',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'privacy_consent' => true,
        ]);

        $bookings = Booking::all();
        $this->assertCount(2, $bookings);
        $this->assertNotEquals(
            $bookings[0]->booking_number,
            $bookings[1]->booking_number
        );
    }

    public function test_phone_arabic_numerals_converted(): void
    {
        $response = $this->post('/en/booking', [
            'full_name' => 'Arabic Phone',
            'phone' => '٠١٢٣٤٥٦٧٨٩٠',
            'booking_type' => 'service',
            'service_id' => $this->service->id,
            'privacy_consent' => true,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'phone' => '01234567890',
        ]);
    }
}

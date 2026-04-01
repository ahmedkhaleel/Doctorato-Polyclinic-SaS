<?php

namespace Tests\Feature\Patient;

use App\Models\Booking;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );
    }

    public function test_patient_registration_creates_new_user_and_patient(): void
    {
        $response = $this->post('/en/patient/register', [
            'full_name' => 'New Patient',
            'phone' => '01099887766',
            'email' => 'newpatient@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newpatient@test.com']);
        $this->assertDatabaseHas('patients', ['phone' => '01099887766']);
    }

    public function test_registration_does_not_link_existing_patient_by_phone(): void
    {
        // Create existing patient without user (added by admin/secretary)
        $existingPatient = new Patient(['full_name' => 'Existing', 'phone' => '01099887766']);
        $existingPatient->file_number = 'P-EXISTING';
        $existingPatient->is_active = true;
        $existingPatient->save();

        $response = $this->post('/en/patient/register', [
            'full_name' => 'New User',
            'phone' => '01099887766',
            'email' => 'newuser@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        // Should create a NEW patient, not link to existing one
        $this->assertEquals(2, Patient::where('phone', '01099887766')->count());

        // The existing patient should NOT have a user_id
        $existingPatient->refresh();
        $this->assertNull($existingPatient->user_id);
    }

    public function test_registration_links_anonymous_bookings_by_phone_and_email(): void
    {
        // Create anonymous booking with matching phone AND email
        $booking = Booking::create([
            'full_name' => 'Anonymous User',
            'booking_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'phone' => '01099887766',
            'email' => 'match@test.com',
            'status' => 'unconfirmed',
            'booking_type' => 'service',
            'module' => 'derma',
            'source' => 'website',
        ]);

        // Create another anonymous booking with same phone but DIFFERENT email
        $bookingDifferentEmail = Booking::create([
            'full_name' => 'Anonymous User 2',
            'booking_date' => now()->addDay()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '11:30',
            'phone' => '01099887766',
            'email' => 'other@test.com',
            'status' => 'unconfirmed',
            'booking_type' => 'service',
            'module' => 'derma',
            'source' => 'website',
        ]);

        $this->post('/en/patient/register', [
            'full_name' => 'New User',
            'phone' => '01099887766',
            'email' => 'match@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $booking->refresh();
        $bookingDifferentEmail->refresh();

        // Only the booking with BOTH phone AND email matching should be linked
        $this->assertNotNull($booking->patient_id);
        $this->assertNull($bookingDifferentEmail->patient_id);
    }

    public function test_registration_validation_requires_all_fields(): void
    {
        $response = $this->post('/en/patient/register', []);

        $response->assertSessionHasErrors(['full_name', 'phone', 'email', 'password']);
    }

    public function test_duplicate_email_rejected(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'taken@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $response = $this->post('/en/patient/register', [
            'full_name' => 'New',
            'phone' => '01099887766',
            'email' => 'taken@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

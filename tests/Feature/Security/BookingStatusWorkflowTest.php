<?php

namespace Tests\Feature\Security;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;
    private Patient $patient;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $secretaryRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            [
                'display_name_en' => 'Secretary',
                'display_name_ar' => 'سكرتير',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            [
                'display_name_en' => 'Doctor',
                'display_name_ar' => 'طبيب',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        $this->secretary = User::create([
            'name' => 'Secretary',
            'email' => 'secretary@test.com',
            'password' => bcrypt('password'),
            'role_id' => $secretaryRole->id,
            'is_active' => true,
        ]);

        $doctorUser = User::create([
            'name' => 'Doctor',
            'email' => 'doctor@test.com',
            'password' => bcrypt('password'),
            'role_id' => $doctorRole->id,
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور تست',
            'name_en' => 'Test Doctor',
            'user_id' => $doctorUser->id,
            'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Test Patient', 'phone' => '01000000002']);
        $this->patient->file_number = 'P-TEST-002';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    private function createBooking(string $status = 'unconfirmed'): Booking
    {
        return Booking::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => $status,
            'booking_type' => 'service',
            'module' => 'derma',
            'source' => 'secretary',
        ]);
    }

    public function test_unconfirmed_can_be_confirmed(): void
    {
        $this->actingAs($this->secretary);
        $booking = $this->createBooking('unconfirmed');

        $response = $this->post("/secretary/bookings/{$booking->id}/status", [
            'status' => 'confirmed',
        ]);

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_unconfirmed_can_be_cancelled(): void
    {
        $this->actingAs($this->secretary);
        $booking = $this->createBooking('unconfirmed');

        $response = $this->post("/secretary/bookings/{$booking->id}/status", [
            'status' => 'cancelled',
        ]);

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    public function test_unconfirmed_cannot_skip_to_completed(): void
    {
        $this->actingAs($this->secretary);
        $booking = $this->createBooking('unconfirmed');

        $response = $this->post("/secretary/bookings/{$booking->id}/status", [
            'status' => 'completed',
        ]);

        $booking->refresh();
        // Should still be unconfirmed — transition blocked
        $this->assertEquals('unconfirmed', $booking->status);
    }

    public function test_completed_cannot_be_changed(): void
    {
        $this->actingAs($this->secretary);
        $booking = $this->createBooking('completed');

        $response = $this->post("/secretary/bookings/{$booking->id}/status", [
            'status' => 'cancelled',
        ]);

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
    }

    public function test_cancelled_can_be_rebooked(): void
    {
        $this->actingAs($this->secretary);
        $booking = $this->createBooking('cancelled');

        $response = $this->post("/secretary/bookings/{$booking->id}/status", [
            'status' => 'unconfirmed',
        ]);

        $booking->refresh();
        $this->assertEquals('unconfirmed', $booking->status);
    }
}

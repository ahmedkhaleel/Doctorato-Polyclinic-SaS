<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\Patient;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingModelTest extends TestCase
{
    use RefreshDatabase;

    // ─── Booking Number Generation ─────────────────────

    public function test_booking_number_generation_returns_unique_prefix(): void
    {
        $number1 = Booking::generateBookingNumber();
        $this->assertStringStartsWith('BK-', $number1);

        // Create a booking so the next number is different
        Booking::create([
            'booking_number' => $number1,
            'source' => 'admin',
            'status' => 'confirmed',
            'full_name' => 'Test Person',
            'phone' => '0123456',
            'booking_type' => 'service',
        ]);

        $number2 = Booking::generateBookingNumber();
        $this->assertNotEquals($number1, $number2);
        $this->assertStringStartsWith('BK-', $number2);
    }

    // ─── Database Constraints ──────────────────────────

    public function test_booking_requires_full_name(): void
    {
        $this->expectException(QueryException::class);

        Booking::create([
            'booking_number' => 'BK-TEST-0001',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => null, // NOT NULL constraint
            'phone' => '0123456',
            'booking_type' => 'service',
        ]);
    }

    // ─── Booking Type Enum ─────────────────────────────

    public function test_booking_type_enum_rejects_invalid_values(): void
    {
        $this->expectException(QueryException::class);

        Booking::create([
            'booking_number' => 'BK-TEST-0002',
            'source' => 'website',
            'status' => 'unconfirmed',
            'full_name' => 'Test',
            'phone' => '0123456',
            'booking_type' => 'consultation', // Invalid enum value
        ]);
    }

    // ─── Relationships ─────────────────────────────────

    public function test_booking_has_patient_relationship(): void
    {
        $patient = new Patient(['full_name' => 'Relationship Test', 'phone' => '01234567890']);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        $booking = Booking::create([
            'booking_number' => 'BK-TEST-0003',
            'source' => 'admin',
            'status' => 'confirmed',
            'full_name' => 'Relationship Test',
            'phone' => '01234567890',
            'booking_type' => 'dermatology_consultation',
            'patient_id' => $patient->id,
        ]);

        $this->assertInstanceOf(Patient::class, $booking->patient);
        $this->assertEquals($patient->id, $booking->patient->id);
    }

    public function test_booking_has_appointments_relationship(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-TEST-0004',
            'source' => 'admin',
            'status' => 'confirmed',
            'full_name' => 'Appointment Test',
            'phone' => '0123456',
            'booking_type' => 'service',
        ]);

        $this->assertCount(0, $booking->appointments);
        $this->assertTrue($booking->appointments()->getRelated() instanceof BookingAppointment);
    }
}

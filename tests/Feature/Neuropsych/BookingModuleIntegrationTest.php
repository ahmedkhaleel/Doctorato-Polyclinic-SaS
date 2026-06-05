<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Booking;
use App\Services\BookingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F1 — psychiatry & neurology (and obgyn) flow through the booking pipeline
 * exactly like the other medical specialties: a booking_type maps to the
 * owning module, and a website booking lands with the right module + type.
 */
class BookingModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_from_booking_type_covers_all_six_specialties(): void
    {
        $map = [
            'dental_consultation' => 'dental',
            'pediatric_service' => 'pediatric',
            'obgyn_consultation' => 'obgyn',
            'psychiatry_consultation' => 'psychiatry',
            'psychiatry_service' => 'psychiatry',
            'neurology_consultation' => 'neurology',
            'neurology_service' => 'neurology',
            'dermatology_consultation' => 'derma',
        ];
        foreach ($map as $type => $module) {
            $this->assertSame($module, BookingWorkflowService::moduleFromBookingType($type), "booking_type {$type} should map to {$module}");
        }

        // Module-agnostic types return null (caller falls back to explicit module).
        $this->assertNull(BookingWorkflowService::moduleFromBookingType('service'));
        $this->assertNull(BookingWorkflowService::moduleFromBookingType('package_bundle'));
        $this->assertNull(BookingWorkflowService::moduleFromBookingType(null));
    }

    public function test_website_booking_for_psychiatry_lands_with_correct_module(): void
    {
        $booking = app(BookingWorkflowService::class)->createFromWebsite([
            'full_name' => 'Test Patient',
            'phone' => '0500001234',
            'booking_type' => 'psychiatry_consultation',
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame('psychiatry', $booking->module);
        $this->assertSame('psychiatry_consultation', $booking->booking_type);
    }

    public function test_website_booking_for_neurology_lands_with_correct_module(): void
    {
        $booking = app(BookingWorkflowService::class)->createFromWebsite([
            'full_name' => 'Test Patient 2',
            'phone' => '0500005678',
            'booking_type' => 'neurology_consultation',
        ]);

        $this->assertSame('neurology', $booking->module);
        $this->assertSame('neurology_consultation', $booking->booking_type);
    }
}

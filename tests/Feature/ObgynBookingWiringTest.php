<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Services\BookingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OB/GYN is a medical module but was not wired into the booking flows
 * (validation + module detection). These guard that an obgyn booking is
 * recognised end-to-end through the workflow service.
 */
class ObgynBookingWiringTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'OB Patient', 'phone' => '0109'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-OB-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    public function test_secretary_obgyn_booking_detects_obgyn_module(): void
    {
        $patient = $this->patient();
        $doctor = \App\Models\Doctor::create(['name_ar' => 'د', 'name_en' => 'OB Dr', 'status' => 'active', 'module' => 'obgyn']);

        $result = app(BookingWorkflowService::class)->createFromSecretary([
            'patient_id' => $patient->id,
            'full_name' => $patient->full_name,
            'phone' => $patient->phone,
            'source' => 'secretary',
            'booking_type' => 'obgyn_consultation',
            'services' => [[
                'service_id' => null,
                'doctor_id' => $doctor->id,
                'sessions_count' => 1,
                'unit_price' => 200,
                'discount_per_session' => 0,
                'appointments' => [[
                    'doctor_id' => $doctor->id,
                    'date' => now()->addDay()->toDateString(),
                    'start_time' => '10:00',
                    'end_time' => '10:30',
                ]],
            ]],
        ], 1);

        $this->assertSame('obgyn', $result['booking']->module);
        $this->assertSame('obgyn_consultation', $result['booking']->booking_type);
    }

    public function test_website_obgyn_booking_detects_obgyn_module(): void
    {
        $booking = app(BookingWorkflowService::class)->createFromWebsite([
            'full_name' => 'OB Web', 'phone' => '01088776655',
            'booking_type' => 'obgyn_consultation',
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame('obgyn', $booking->module);
    }
}

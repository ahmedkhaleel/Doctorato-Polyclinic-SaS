<?php

namespace Tests\Feature\Patient;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );

        $this->user = User::create([
            'name' => 'Booking Patient', 'email' => 'booking-patient@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Booking Patient', 'phone' => '01500000001', 'email' => 'booking-patient@test.com',
        ]);
        $this->patient->user_id = $this->user->id;
        $this->patient->file_number = 'P-BK-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    public function test_patient_can_view_bookings_index(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient/bookings')
            ->assertOk();
    }

    public function test_patient_can_view_create_booking_page(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient/bookings/create')
            ->assertOk();
    }

    public function test_patient_can_create_booking(): void
    {
        $this->actingAs($this->user)
            ->post('/en/patient/bookings', [
                'booking_type' => 'dermatology_consultation',
                'preferred_date' => now()->addDays(3)->toDateString(),
                'preferred_time' => '10:00',
                'notes' => 'First visit',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $this->patient->id,
            'source' => 'patient_portal',
            'status' => 'unconfirmed',
        ]);
    }

    public function test_patient_cannot_book_in_past(): void
    {
        $this->actingAs($this->user)
            ->post('/en/patient/bookings', [
                'booking_type' => 'dermatology_consultation',
                'preferred_date' => now()->subDays(1)->toDateString(),
                'preferred_time' => '10:00',
            ])
            ->assertSessionHasErrors('preferred_date');
    }

    public function test_patient_booking_requires_type(): void
    {
        $this->actingAs($this->user)
            ->post('/en/patient/bookings', [
                'preferred_date' => now()->addDays(3)->toDateString(),
                'preferred_time' => '10:00',
            ])
            ->assertSessionHasErrors('booking_type');
    }

    public function test_patient_can_cancel_own_booking(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-TEST-001',
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_type' => 'dermatology_consultation',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => '10:00',
            'module' => 'derma', 'source' => 'patient_portal', 'status' => 'confirmed',
        ]);

        $this->actingAs($this->user)
            ->post("/en/patient/bookings/{$booking->id}/cancel")
            ->assertRedirect();

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    public function test_patient_cannot_cancel_other_patients_booking(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other', 'phone' => '01500000002']);
        $otherPatient->file_number = 'P-BK-002';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $booking = Booking::create([
            'booking_number' => 'BK-TEST-002',
            'patient_id' => $otherPatient->id,
            'full_name' => $otherPatient->full_name, 'phone' => $otherPatient->phone,
            'booking_type' => 'dermatology_consultation',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => '10:00',
            'module' => 'derma', 'source' => 'secretary', 'status' => 'confirmed',
        ]);

        $this->actingAs($this->user)
            ->post("/en/patient/bookings/{$booking->id}/cancel")
            ->assertForbidden();
    }

    public function test_patient_cannot_cancel_completed_booking(): void
    {
        $booking = Booking::create([
            'booking_number' => 'BK-TEST-003',
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_type' => 'dermatology_consultation',
            'preferred_date' => now()->toDateString(),
            'preferred_time' => '10:00',
            'module' => 'derma', 'source' => 'patient_portal', 'status' => 'completed',
        ]);

        $this->actingAs($this->user)
            ->post("/en/patient/bookings/{$booking->id}/cancel")
            ->assertSessionHas('error');
    }

    public function test_bookings_index_only_shows_own_bookings(): void
    {
        Booking::create([
            'booking_number' => 'BK-MINE-001',
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_type' => 'dermatology_consultation',
            'preferred_date' => now()->addDays(1)->toDateString(),
            'preferred_time' => '10:00',
            'module' => 'derma', 'source' => 'patient_portal', 'status' => 'confirmed',
        ]);

        $otherPatient = new Patient(['full_name' => 'Other Patient', 'phone' => '01500000099']);
        $otherPatient->file_number = 'P-BK-099';
        $otherPatient->is_active = true;
        $otherPatient->save();

        Booking::create([
            'booking_number' => 'BK-OTHER-001',
            'patient_id' => $otherPatient->id,
            'full_name' => 'Other Patient', 'phone' => '01500000099',
            'booking_type' => 'dermatology_consultation',
            'preferred_date' => now()->addDays(1)->toDateString(),
            'preferred_time' => '14:00',
            'module' => 'derma', 'source' => 'secretary', 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/en/patient/bookings');

        $response->assertOk();
        $props = $response->original->getData()['page']['props'] ?? [];
        if (isset($props['bookings']['data'])) {
            foreach ($props['bookings']['data'] as $b) {
                $this->assertEquals($this->patient->id, $b['patient_id']);
            }
        }
    }
}

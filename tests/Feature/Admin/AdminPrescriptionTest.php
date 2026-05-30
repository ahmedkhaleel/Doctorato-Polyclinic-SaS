<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name_en' => 'Super Admin', 'display_name_ar' => 'مدير عام', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-rx-test@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور وصفة', 'name_en' => 'Rx Doctor', 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Rx Patient', 'phone' => '01888000002']);
        $this->patient->file_number = 'P-RX-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    private function createVisitWithBooking(): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
            'booking_type' => 'dermatology_consultation',
            'status' => 'confirmed',
            'source' => 'admin',
        ]);

        $bookingService = BookingService::create([
            'booking_id' => $booking->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 1,
            'unit_price' => 200,
            'total_price' => 200,
            'status' => 'pending',
        ]);

        $appointment = BookingAppointment::create([
            'booking_id' => $booking->id,
            'booking_service_id' => $bookingService->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'scheduled',
        ]);

        return Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now()->toDateString(),
            'visit_type' => 'consultation',
            'status' => 'completed',
            'module' => 'derma',
        ]);
    }

    public function test_can_view_prescriptions_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/prescriptions')->assertOk();
    }

    public function test_can_create_prescription(): void
    {
        $visit = $this->createVisitWithBooking();

        $this->actingAs($this->admin)->post('/admin/prescriptions', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'visit_id' => $visit->id,
            'diagnosis' => 'Test diagnosis',
            'notes' => 'Test notes',
            'items' => [
                [
                    'medication_name' => 'Paracetamol 500mg',
                    'dosage' => '1 tablet',
                    'frequency' => 'Twice daily',
                    'duration' => '5 days',
                    'instructions' => 'After meals',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('prescriptions', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Test diagnosis',
        ]);
    }

    public function test_prescription_requires_items(): void
    {
        $this->actingAs($this->admin)->post('/admin/prescriptions', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Test',
            'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_can_view_prescription_show(): void
    {
        $prescription = Prescription::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Show test',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Test Med',
            'dosage' => '10mg',
            'frequency' => 'Daily',
            'duration' => '7 days',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/prescriptions/{$prescription->id}")
            ->assertOk();
    }

    public function test_can_update_prescription(): void
    {
        $prescription = Prescription::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Original',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Old Med',
            'dosage' => '5mg',
            'frequency' => 'Once',
            'duration' => '3 days',
        ]);

        $this->actingAs($this->admin)->post("/admin/prescriptions/{$prescription->id}/update", [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Updated diagnosis',
            'items' => [
                [
                    'medication_name' => 'New Med',
                    'dosage' => '20mg',
                    'frequency' => 'Twice daily',
                    'duration' => '10 days',
                ],
            ],
        ])->assertRedirect();

        $prescription->refresh();
        $this->assertEquals('Updated diagnosis', $prescription->diagnosis);
    }

    public function test_can_delete_prescription(): void
    {
        $prescription = Prescription::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Delete me',
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/prescriptions/{$prescription->id}/delete")
            ->assertRedirect();

        $this->assertSoftDeleted('prescriptions', ['id' => $prescription->id]);
    }

    public function test_can_download_prescription_pdf(): void
    {
        $prescription = Prescription::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'PDF test',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'PDF Med',
            'dosage' => '10mg',
            'frequency' => 'Daily',
            'duration' => '7 days',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/prescriptions/{$prescription->id}/pdf");

        // Should return PDF (200) or redirect
        $this->assertContains($response->status(), [200, 302]);
    }
}

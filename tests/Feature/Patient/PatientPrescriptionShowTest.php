<?php

namespace Tests\Feature\Patient;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPrescriptionShowTest extends TestCase
{
    use RefreshDatabase;

    private function patientUser(): array
    {
        $role = Role::firstOrCreate(['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $user = User::create(['name' => 'P', 'email' => 'p'.uniqid().'@t.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true]);
        $patient = new Patient(['full_name' => 'Test P', 'phone' => '0100'.rand(1000000, 9999999)]);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->user_id = $user->id;
        $patient->save();

        return [$user, $patient];
    }

    public function test_prescription_show_loads_without_500(): void
    {
        [$user, $patient] = $this->patientUser();
        $rx = Prescription::create(['patient_id' => $patient->id]);
        PrescriptionItem::create([
            'prescription_id' => $rx->id, 'medication_name' => 'Amoxicillin',
            'dosage' => '500mg', 'frequency' => 'twice daily', 'duration' => '7 days',
        ]);

        $this->actingAs($user)->get("/ar/patient/prescriptions/{$rx->id}")->assertOk();
    }

    public function test_visit_show_with_prescription_loads(): void
    {
        [$user, $patient] = $this->patientUser();
        $booking = \App\Models\Booking::create([
            'patient_id' => $patient->id, 'booking_number' => \App\Models\Booking::generateBookingNumber(),
            'source' => 'clinic', 'status' => 'completed',
            'full_name' => $patient->full_name, 'phone' => $patient->phone,
        ]);
        $visit = Visit::create([
            'patient_id' => $patient->id, 'booking_id' => $booking->id, 'visit_type' => 'consultation',
            'status' => 'completed', 'visit_date' => now()->toDateString(),
        ]);
        $rx = Prescription::create(['patient_id' => $patient->id, 'visit_id' => $visit->id]);
        PrescriptionItem::create(['prescription_id' => $rx->id, 'medication_name' => 'Ibuprofen', 'dosage' => '400mg']);

        $this->actingAs($user)->get("/ar/patient/visits/{$visit->id}")->assertOk();
    }

    public function test_medications_relation_returns_items(): void
    {
        [$user, $patient] = $this->patientUser();
        $rx = Prescription::create(['patient_id' => $patient->id]);
        PrescriptionItem::create(['prescription_id' => $rx->id, 'medication_name' => 'Paracetamol']);

        $this->assertSame(1, $rx->medications()->count());
        $this->assertSame('Paracetamol', $rx->medications()->first()->medication_name);
    }
}

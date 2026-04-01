<?php

namespace Tests\Feature\Patient;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );

        $this->user = User::create([
            'name' => 'Portal Patient', 'email' => 'portal-patient@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Portal Patient', 'phone' => '01600000001', 'email' => 'portal-patient@test.com',
        ]);
        $this->patient->user_id = $this->user->id;
        $this->patient->file_number = 'P-PORTAL-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور بوابة', 'name_en' => 'Portal Doctor', 'status' => 'active',
        ]);
    }

    private function createVisitWithBooking(array $visitOverrides = []): Visit
    {
        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '10:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);

        return Visit::create(array_merge([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'module' => 'derma',
            'status' => 'completed', 'visit_date' => now()->toDateString(),
        ], $visitOverrides));
    }

    // ─── Dashboard ──────────────────────────────────────
    public function test_patient_can_access_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient')
            ->assertOk();
    }

    public function test_dashboard_has_stats(): void
    {
        $response = $this->actingAs($this->user)->get('/en/patient');
        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertArrayHasKey('stats', $props);
        $this->assertArrayHasKey('upcoming_count', $props['stats']);
        $this->assertArrayHasKey('total_visits', $props['stats']);
        $this->assertArrayHasKey('unpaid_count', $props['stats']);
    }

    // ─── Visits ─────────────────────────────────────────
    public function test_patient_can_view_visits_index(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient/visits')
            ->assertOk();
    }

    public function test_patient_can_view_own_visit(): void
    {
        $visit = $this->createVisitWithBooking();

        $this->actingAs($this->user)
            ->get("/en/patient/visits/{$visit->id}")
            ->assertOk();
    }

    public function test_patient_cannot_view_other_patients_visit(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other', 'phone' => '01600000002']);
        $otherPatient->file_number = 'P-PORTAL-002';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $booking = Booking::create([
            'patient_id' => $otherPatient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => 'Other', 'phone' => '01600000002',
            'booking_date' => now()->toDateString(), 'start_time' => '11:00', 'end_time' => '11:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);
        $otherVisit = Visit::create([
            'patient_id' => $otherPatient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'module' => 'derma',
            'status' => 'completed', 'visit_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->get("/en/patient/visits/{$otherVisit->id}")
            ->assertForbidden();
    }

    // ─── Invoices ───────────────────────────────────────
    public function test_patient_can_view_invoices_index(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient/invoices')
            ->assertOk();
    }

    public function test_patient_can_view_own_invoice(): void
    {
        $invoice = Invoice::create([
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-PORTAL-001',
            'total' => 200, 'invoice_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->get("/en/patient/invoices/{$invoice->id}")
            ->assertOk();
    }

    public function test_patient_cannot_view_other_patients_invoice(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other Invoice', 'phone' => '01600000003']);
        $otherPatient->file_number = 'P-PORTAL-003';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $invoice = Invoice::create([
            'patient_id' => $otherPatient->id,
            'invoice_number' => 'INV-PORTAL-002',
            'total' => 500, 'invoice_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->get("/en/patient/invoices/{$invoice->id}")
            ->assertForbidden();
    }

    // ─── Prescriptions ──────────────────────────────────
    public function test_patient_can_view_prescriptions_index(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient/prescriptions')
            ->assertOk();
    }

    public function test_patient_cannot_view_other_patients_prescription(): void
    {
        $otherPatient = new Patient(['full_name' => 'Other Rx', 'phone' => '01600000004']);
        $otherPatient->file_number = 'P-PORTAL-004';
        $otherPatient->is_active = true;
        $otherPatient->save();

        $prescription = Prescription::create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $this->actingAs($this->user)
            ->get("/en/patient/prescriptions/{$prescription->id}")
            ->assertForbidden();
    }

    // ─── Photos ─────────────────────────────────────────
    public function test_patient_can_view_photos(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient/photos')
            ->assertOk();
    }

    // ─── Profile ────────────────────────────────────────
    public function test_patient_can_view_profile(): void
    {
        $this->actingAs($this->user)
            ->get('/en/patient/profile')
            ->assertOk();
    }

    public function test_patient_profile_update_validates_phone(): void
    {
        $this->actingAs($this->user)
            ->post('/en/patient/profile', [
                'emergency_contact_phone' => 'not-a-phone!!!',
            ])
            ->assertSessionHasErrors('emergency_contact_phone');
    }

    // ─── Authentication ─────────────────────────────────
    public function test_unauthenticated_redirected_to_login(): void
    {
        $this->get('/en/patient')
            ->assertRedirect();
    }

    public function test_non_patient_role_cannot_access_portal(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );
        $adminUser = User::create([
            'name' => 'Admin', 'email' => 'admin-portal-test@test.com',
            'password' => bcrypt('password'), 'role_id' => $adminRole->id, 'is_active' => true,
        ]);

        $this->actingAs($adminUser)
            ->get('/en/patient')
            ->assertRedirect();
    }

    public function test_inactive_patient_cannot_access_portal(): void
    {
        // is_active is not fillable — update directly via DB
        \Illuminate\Support\Facades\DB::table('patients')
            ->where('id', $this->patient->id)
            ->update(['is_active' => false]);

        $this->actingAs($this->user)
            ->get('/en/patient')
            ->assertRedirect();
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPatientTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name_en' => 'Super Admin', 'display_name_ar' => 'مدير عام', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->adminUser = User::create([
            'name' => 'Admin', 'email' => 'admin-timeline@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور', 'name_en' => 'Timeline Doctor',
            'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Timeline Patient', 'phone' => '01888000111']);
        $this->patient->file_number = 'P-TL-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    private function createVisitWithBooking(array $visitOverrides = [], ?string $date = null): Visit
    {
        $date = $date ?? now()->toDateString();
        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => $date, 'start_time' => '10:00', 'end_time' => '10:30',
            'status' => 'confirmed', 'booking_type' => 'dermatology_consultation',
            'module' => 'derma', 'source' => 'secretary',
        ]);

        return Visit::create(array_merge([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'module' => 'derma',
            'status' => 'completed', 'visit_date' => $date,
        ], $visitOverrides));
    }

    public function test_admin_can_view_patient_timeline(): void
    {
        $this->actingAs($this->adminUser)
            ->get("/admin/patients/{$this->patient->id}/timeline")
            ->assertOk();
    }

    public function test_timeline_includes_visits(): void
    {
        $this->createVisitWithBooking();

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/patients/{$this->patient->id}/timeline");

        $response->assertOk();
        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertNotEmpty($props['timeline']);

        $types = collect($props['timeline'])->pluck('type')->toArray();
        $this->assertContains('visit', $types);
    }

    public function test_timeline_includes_invoices(): void
    {
        Invoice::create([
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-TL-001',
            'total' => 100, 'paid_amount' => 0, 'status' => 'unpaid',
            'invoice_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/patients/{$this->patient->id}/timeline");

        $props = $response->original->getData()['page']['props'] ?? [];
        $types = collect($props['timeline'])->pluck('type')->toArray();
        $this->assertContains('invoice', $types);
    }

    public function test_timeline_is_sorted_by_date_descending(): void
    {
        $this->createVisitWithBooking([], now()->subDays(5)->toDateString());
        $this->createVisitWithBooking(['visit_type' => 'follow_up']);

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/patients/{$this->patient->id}/timeline");

        $props = $response->original->getData()['page']['props'] ?? [];
        $dates = collect($props['timeline'])->pluck('date')->toArray();

        // Should be sorted descending
        $sorted = $dates;
        rsort($sorted);
        $this->assertEquals($sorted, $dates);
    }

    public function test_patient_show_has_financial_summary(): void
    {
        $invoice = Invoice::create([
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-FS-001',
            'total' => 300,
            'invoice_date' => now()->toDateString(),
        ]);
        // paid_amount and status are not fillable — update via DB
        \Illuminate\Support\Facades\DB::table('invoices')
            ->where('id', $invoice->id)
            ->update(['paid_amount' => 100, 'status' => 'partial']);

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/patients/{$this->patient->id}");

        $response->assertOk();
        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertArrayHasKey('financialSummary', $props);
        $this->assertEquals(300.00, $props['financialSummary']['total_invoiced']);
        $this->assertEquals(100.00, $props['financialSummary']['total_paid']);
        $this->assertEquals(200.00, $props['financialSummary']['outstanding_balance']);
    }

    public function test_patient_show_returns_empty_dental_when_disabled(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/patients/{$this->patient->id}");

        $response->assertOk();
        $props = $response->original->getData()['page']['props'] ?? [];
        // Dental data should be null when module is not enabled
        $this->assertNull($props['dentalData']);
    }
}

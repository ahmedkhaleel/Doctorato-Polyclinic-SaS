<?php

namespace Tests\Feature\Doctor;

use App\Models\Booking;
use App\Models\DentalChart;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorDentalChartTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Chart Doctor', 'email' => 'doc-chart@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور خريطة', 'name_en' => 'Chart Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        // Enable dental module
        DB::table('module_settings')->updateOrInsert(
            ['module' => 'dental', 'key' => 'enabled'],
            ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
        );
        cache()->forget('module_dental_enabled');

        $this->patient = new Patient(['full_name' => 'Chart Patient', 'phone' => '0500009222', 'gender' => 'female']);
        $this->patient->file_number = 'PAT-CH-001';
        $this->patient->is_active = true;
        $this->patient->save();

        // Create a visit linking doctor to patient (Visit requires a booking)
        $booking = Booking::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'full_name' => $this->patient->full_name, 'phone' => $this->patient->phone,
            'booking_date' => today()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30',
            'status' => 'confirmed', 'booking_type' => 'dental_consultation',
            'module' => 'dental', 'source' => 'secretary',
        ]);

        Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'booking_id' => $booking->id,
            'visit_date' => today()->toDateString(),
            'status' => 'completed',
            'visit_type' => 'consultation',
            'module' => 'dental',
        ]);
    }

    public function test_can_search_patients(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/dental/chart-search?search=Chart')
            ->assertOk();
    }

    public function test_can_view_patient_chart(): void
    {
        $this->actingAs($this->doctorUser)
            ->get("/doctor/dental/chart/{$this->patient->id}")
            ->assertOk();
    }

    public function test_can_initialize_chart(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/dental/chart/{$this->patient->id}/initialize", [
                'mode' => 'adult',
            ])
            ->assertRedirect();

        $count = DentalChart::where('patient_id', $this->patient->id)->count();
        $this->assertEquals(count(DentalChart::ALL_TEETH), $count);
    }

    public function test_can_update_tooth(): void
    {
        // Initialize a tooth first
        DentalChart::create([
            'patient_id' => $this->patient->id,
            'tooth_number' => 11,
            'condition' => 'healthy',
            'status' => 'present',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/dental/chart/{$this->patient->id}/tooth/11", [
                'condition' => 'decayed',
                'surfaces' => ['mesial', 'distal'],
                'notes' => 'Needs filling',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dental_charts', [
            'patient_id' => $this->patient->id,
            'tooth_number' => 11,
            'condition' => 'decayed',
        ]);
    }

    public function test_initialize_skips_existing_teeth(): void
    {
        // Pre-create one tooth
        DentalChart::create([
            'patient_id' => $this->patient->id,
            'tooth_number' => 11,
            'condition' => 'decayed',
            'status' => 'present',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/dental/chart/{$this->patient->id}/initialize", [
                'mode' => 'adult',
            ])
            ->assertRedirect();

        // Existing tooth should not be overwritten
        $this->assertDatabaseHas('dental_charts', [
            'patient_id' => $this->patient->id,
            'tooth_number' => 11,
            'condition' => 'decayed',
        ]);

        $count = DentalChart::where('patient_id', $this->patient->id)->count();
        $this->assertEquals(count(DentalChart::ALL_TEETH), $count);
    }
}

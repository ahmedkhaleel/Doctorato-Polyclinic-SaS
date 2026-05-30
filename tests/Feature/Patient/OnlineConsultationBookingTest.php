<?php

namespace Tests\Feature\Patient;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the online-consultation booking flow against the regressions
 * that ate a whole debugging session:
 *
 *   1. GET /{locale}/patient/online-consultations/doctors — lists only
 *      doctors with online_consultation_enabled=true.
 *   2. GET /{locale}/patient/online-consultations/book/{docId} — renders
 *      the Book page even though the URL carries TWO route params
 *      ({locale}, {docId}) — this was the positional-param 404.
 *   3. availability returned from showDoctor must include at least one
 *      day with has_available=true when the doctor has a both-mode
 *      schedule — otherwise the page loads but every day is greyed out.
 */
class OnlineConsultationBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Turn the module on so Patient routes/pages don't bail.
        ModuleManager::enable('telemedicine');

        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );

        $this->patientUser = User::create([
            'name' => 'Test Patient',
            'email' => 'patient-booking@test.com',
            'password' => bcrypt('password'),
            'role_id' => $patientRole->id,
            'is_active' => true,
        ]);

        // user_id + is_active are guarded against mass assignment — set directly.
        $patient = Patient::create([
            'full_name' => 'Test Patient',
            'phone' => '+971500000000',
        ]);
        $patient->forceFill(['user_id' => $this->patientUser->id, 'is_active' => true])->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'د. اختبار', 'name_en' => 'Dr. Test',
            'specialization_en' => 'Dermatology',
            'doctor_type' => 'specialist',
            'status' => 'active',
            'module' => 'derma',
            'online_consultation_enabled' => true,
            'online_consultation_fee' => 250.00,
            'online_session_duration_minutes' => 30,
        ]);

        // A 'both'-mode schedule tomorrow (whatever weekday that is) so
        // the slot generator returns available slots.
        $tomorrow = Carbon::tomorrow();
        $systemDay = [0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 0][$tomorrow->dayOfWeek];
        DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => $systemDay,
            'start_time' => '10:00:00',
            'end_time' => '13:00:00',
            'mode' => 'both',
            'is_active' => true,
        ]);
    }

    public function test_doctors_list_shows_only_online_enabled_doctors(): void
    {
        // A second doctor that is NOT online-enabled — must not leak into
        // the list.
        Doctor::create([
            'name_ar' => 'د. كلينك فقط', 'name_en' => 'Dr. Clinic Only',
            'specialization_en' => 'Dental',
            'doctor_type' => 'specialist',
            'status' => 'active',
            'module' => 'dental',
            'online_consultation_enabled' => false,
            'online_consultation_fee' => 200.00,
        ]);

        $response = $this->actingAs($this->patientUser)
            ->get('/ar/patient/online-consultations/doctors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Patient/OnlineConsultation/Doctors')
            ->has('doctors', 1)
            ->where('doctors.0.id', $this->doctor->id)
        );
    }

    public function test_book_page_loads_with_locale_and_doc_id_in_url(): void
    {
        // This is the exact regression: {locale}=ar + {docId}=N both present.
        $response = $this->actingAs($this->patientUser)
            ->get("/ar/patient/online-consultations/book/{$this->doctor->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Patient/OnlineConsultation/Book')
            ->where('doctor.id', $this->doctor->id)
            ->has('availability', 14)
        );
    }

    public function test_book_page_returns_404_for_doctor_not_online_enabled(): void
    {
        $this->doctor->update(['online_consultation_enabled' => false]);

        $this->actingAs($this->patientUser)
            ->get("/ar/patient/online-consultations/book/{$this->doctor->id}")
            ->assertNotFound();
    }

    public function test_availability_includes_at_least_one_bookable_day(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->get("/ar/patient/online-consultations/book/{$this->doctor->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('availability', function ($availability) {
                $withSlots = collect($availability)->where('has_available', true);

                return $withSlots->isNotEmpty();
            })
        );
    }
}

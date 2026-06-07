<?php

namespace Tests\Feature\Physio;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\BookingWorkflowService;
use App\Services\CommissionCalculator;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * PT-6 — physiotherapy is fully bookable end to end: the booking-type enums
 * accept it, a secretary-created physiotherapy_consultation booking turns into a
 * visit tagged module=physiotherapy + consultation_type=physiotherapy, and the
 * resulting visit resolves the module fee + commission.
 */
class PhysiotherapyBookingTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();
        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name_en' => 'SA', 'display_name_ar' => 'SA', 'permissions' => ['*'], 'is_system' => true]);
        $this->staff = User::create(['name' => 'Staff', 'email' => 'pt-book@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT Doc', 'status' => 'active', 'module' => 'physiotherapy', 'physiotherapy_consultation_commission' => 50]);
    }

    public function test_booking_type_enums_accept_physiotherapy(): void
    {
        foreach (['physiotherapy_consultation', 'physiotherapy_session'] as $type) {
            $v = Validator::make(['booking_type' => $type], (new \App\Http\Requests\StoreBookingRequest)->rules());
            $this->assertFalse($v->errors()->has('booking_type'), "StoreBookingRequest should accept {$type}");
        }
    }

    public function test_consultation_booking_creates_a_physiotherapy_visit(): void
    {
        $patient = Patient::create(['full_name' => 'Booker', 'phone' => '0500005555']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-BK-1'])->save();

        $workflow = app(BookingWorkflowService::class);
        $result = $workflow->createFromSecretary([
            'patient_id' => $patient->id,
            'full_name' => $patient->full_name,
            'phone' => $patient->phone,
            'booking_type' => 'physiotherapy_consultation',
            'source' => 'secretary',
            'services' => [[
                'doctor_id' => $this->doctor->id,
                'unit_price' => 250,
                'sessions_count' => 1,
                'appointments' => [[
                    'date' => now()->toDateString(),
                    'start_time' => '10:00',
                    'end_time' => '10:45',
                    'doctor_id' => $this->doctor->id,
                ]],
            ]],
        ], $this->staff->id);

        $booking = $result['booking'];
        $this->assertSame('physiotherapy', $booking->module);

        $visits = $workflow->createVisitsForTodayAppointments($booking, $this->staff->id);
        $this->assertNotEmpty($visits);

        $visit = \App\Models\Visit::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('physiotherapy', $visit->module);
        $this->assertSame('physiotherapy', $visit->consultation_type);
        $this->assertSame('consultation', $visit->visit_type);

        // The visit resolves the per-doctor module commission (50%).
        $visit->setRelation('doctor', $this->doctor);
        $result = app(CommissionCalculator::class)->calculate($visit, 250);
        $this->assertEqualsWithDelta(125.0, $result['commission_amount'], 0.01);
    }
}

<?php

namespace Tests\Feature\Commands;

use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreateDailyVisitsCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Doctor $doctor;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Test Patient',
            'phone' => '0500000000',
            'gender' => 'male',
        ]);
        $this->patient->file_number = Patient::generateFileNumber();
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور اختبار',
            'name_en' => 'Test Doctor',
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Dermatology',
            'status' => 'active',
            'user_id' => $this->admin->id,
        ]);

        $category = ServiceCategory::create([
            'module' => 'derma',
            'name_ar' => 'تصنيف اختبار',
            'name_en' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $this->service = Service::create([
            'category_id' => $category->id,
            'name_ar' => 'خدمة اختبار',
            'name_en' => 'Test Service',
            'slug' => 'test-service',
            'status' => 'active',
            'module' => 'derma',
            'price' => 100,
        ]);
    }

    /**
     * Create an in-progress booking with a service and today's appointment.
     */
    private static int $timeSlotCounter = 0;

    private function createBookingWithTodayAppointment(
        string $bookingStatus = 'in_progress',
        ?string $appointmentDate = null,
        string $appointmentStatus = 'scheduled',
        ?int $visitId = null,
        ?string $startTime = null,
    ): array {
        if ($startTime === null) {
            $hour = 8 + self::$timeSlotCounter;
            $startTime = sprintf('%02d:00', $hour);
        }
        self::$timeSlotCounter++;

        $endMinutes = ((int) substr($startTime, 0, 2)) * 60 + (int) substr($startTime, 3, 2) + 30;
        $endTime = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);

        $booking = Booking::create([
            'booking_number' => Booking::generateBookingNumber(),
            'patient_id' => $this->patient->id,
            'source' => 'secretary',
            'module' => 'derma',
            'booking_type' => 'service',
            'status' => $bookingStatus,
            'full_name' => $this->patient->full_name,
            'phone' => $this->patient->phone,
        ]);
        $booking->created_by = $this->admin->id;
        $booking->save();

        $bookingService = BookingService::create([
            'booking_id' => $booking->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 3,
            'unit_price' => 100,
            'discount_per_session' => 0,
            'total_price' => 300,
            'completed_sessions' => 0,
            'status' => 'pending',
        ]);

        $appointment = BookingAppointment::create([
            'booking_id' => $booking->id,
            'booking_service_id' => $bookingService->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate ?? today()->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'session_number' => 1,
            'status' => $appointmentStatus,
            'visit_id' => $visitId,
        ]);

        return compact('booking', 'bookingService', 'appointment');
    }

    public function test_command_creates_visits_for_todays_appointments(): void
    {
        $data = $this->createBookingWithTodayAppointment();

        $this->artisan('bookings:create-daily-visits')
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 1);

        $visit = Visit::first();
        $this->assertEquals($this->patient->id, $visit->patient_id);
        $this->assertEquals($this->doctor->id, $visit->doctor_id);
        $this->assertEquals($data['booking']->id, $visit->booking_id);
        $this->assertEquals($data['appointment']->id, $visit->booking_appointment_id);
        $this->assertEquals('waiting', $visit->status);
        $this->assertEquals(today()->toDateString(), $visit->visit_date->toDateString());

        // Assert appointment was updated with visit_id
        $data['appointment']->refresh();
        $this->assertNotNull($data['appointment']->visit_id);
        $this->assertEquals($visit->id, $data['appointment']->visit_id);
        $this->assertEquals('checked_in', $data['appointment']->status);
    }

    public function test_command_skips_bookings_not_in_progress(): void
    {
        $this->createBookingWithTodayAppointment(bookingStatus: 'confirmed');

        $this->artisan('bookings:create-daily-visits')
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_command_skips_appointments_not_for_today(): void
    {
        $this->createBookingWithTodayAppointment(
            appointmentDate: now()->addDay()->toDateString(),
        );

        $this->artisan('bookings:create-daily-visits')
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_command_skips_cancelled_appointments(): void
    {
        $this->createBookingWithTodayAppointment(appointmentStatus: 'cancelled');

        $this->artisan('bookings:create-daily-visits')
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_command_skips_no_show_appointments(): void
    {
        $this->createBookingWithTodayAppointment(appointmentStatus: 'no_show');

        $this->artisan('bookings:create-daily-visits')
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_command_skips_completed_appointments(): void
    {
        $this->createBookingWithTodayAppointment(appointmentStatus: 'completed');

        $this->artisan('bookings:create-daily-visits')
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_command_skips_appointments_with_existing_visit(): void
    {
        // Create a dummy visit first to use as the existing visit_id
        $tempData = $this->createBookingWithTodayAppointment();

        // Manually create a visit to link to
        $existingVisit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'receptionist_id' => $this->admin->id,
            'booking_id' => $tempData['booking']->id,
            'booking_appointment_id' => $tempData['appointment']->id,
            'visit_type' => 'session',
            'service_id' => $this->service->id,
            'session_number' => 1,
            'status' => 'waiting',
            'visit_date' => today(),
            'scheduled_time' => '10:00',
        ]);

        // Now mark that appointment as already having a visit
        $tempData['appointment']->update(['visit_id' => $existingVisit->id]);

        $visitCountBefore = Visit::count();

        $this->artisan('bookings:create-daily-visits')
            ->assertSuccessful();

        // No new visits should have been created
        $this->assertEquals($visitCountBefore, Visit::count());
    }

    public function test_command_returns_success(): void
    {
        $this->artisan('bookings:create-daily-visits')
            ->assertExitCode(0);
    }

    public function test_command_outputs_correct_count(): void
    {
        // Create 2 bookings, each with a today appointment
        $this->createBookingWithTodayAppointment();
        $this->createBookingWithTodayAppointment();

        $this->artisan('bookings:create-daily-visits')
            ->expectsOutputToContain("Created 2 visit(s) for today's appointments across 2 booking(s).")
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 2);
    }

    public function test_command_handles_multiple_appointments_per_booking(): void
    {
        $data = $this->createBookingWithTodayAppointment();

        // Add a second appointment to the same booking for today with a different time
        $secondTime = sprintf('%02d:00', 8 + self::$timeSlotCounter);
        self::$timeSlotCounter++;
        BookingAppointment::create([
            'booking_id' => $data['booking']->id,
            'booking_service_id' => $data['bookingService']->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => today()->toDateString(),
            'start_time' => $secondTime,
            'end_time' => sprintf('%02d:30', (int) substr($secondTime, 0, 2)),
            'session_number' => 2,
            'status' => 'scheduled',
        ]);

        $this->artisan('bookings:create-daily-visits')
            ->expectsOutputToContain("Created 2 visit(s) for today's appointments across 1 booking(s).")
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 2);
    }

    public function test_command_does_not_create_visits_when_no_qualifying_bookings(): void
    {
        $this->artisan('bookings:create-daily-visits')
            ->expectsOutputToContain("Created 0 visit(s) for today's appointments across 0 booking(s).")
            ->assertSuccessful();

        $this->assertDatabaseCount('visits', 0);
    }
}

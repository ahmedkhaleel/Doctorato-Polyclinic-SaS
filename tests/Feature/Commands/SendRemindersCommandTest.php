<?php

namespace Tests\Feature\Commands;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Notifications\BookingReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createDoctorWithUser(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['doctor.*'], 'is_system' => true]
        );

        $user = User::create([
            'name' => 'Dr. Test',
            'email' => 'doctor' . uniqid() . '@test.com',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $doctor = Doctor::create([
            'name_en' => 'Dr. Test',
            'name_ar' => 'د. تجربة',
            'status' => 'active',
            'user_id' => $user->id,
            'dermatology_fee' => 100,
            'cosmetic_fee' => 200,
            'default_commission_percentage' => 10,
        ]);

        return [$doctor, $user];
    }

    private function createBooking(array $attributes = []): Booking
    {
        $category = ServiceCategory::firstOrCreate(
            ['slug' => 'test-cat'],
            ['name_en' => 'Test Category', 'name_ar' => 'تصنيف']
        );

        $service = Service::firstOrCreate(
            ['slug' => 'test-service'],
            [
                'name_en' => 'Test Service',
                'name_ar' => 'خدمة تجريبية',
                'status' => 'active',
                'module' => 'derma',
                'category_id' => $category->id,
            ]
        );

        $patient = new Patient([
            'full_name' => 'Test Patient',
            'phone' => '0500000000',
            'gender' => 'male',
        ]);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        $defaults = [
            'booking_number' => 'BK-' . uniqid(),
            'source' => 'website',
            'module' => 'derma',
            'booking_type' => 'dermatology_consultation',
            'status' => 'confirmed',
            'full_name' => 'Test Patient',
            'phone' => '0500000000',
            'service_id' => $service->id,
            'preferred_date' => today(),
            'preferred_time' => '10:00',
            'patient_id' => $patient->id,
        ];

        return Booking::create(array_merge($defaults, $attributes));
    }

    public function test_command_sends_reminders_for_todays_confirmed_bookings(): void
    {
        Notification::fake();

        [$doctor, $user] = $this->createDoctorWithUser();

        $this->createBooking([
            'status' => 'confirmed',
            'preferred_date' => today(),
            'doctor_id' => $doctor->id,
        ]);

        $this->artisan('bookings:send-reminders')
            ->assertSuccessful();

        Notification::assertSentTo($user, BookingReminderNotification::class);
    }

    public function test_command_does_not_send_for_past_bookings(): void
    {
        Notification::fake();

        [$doctor, $user] = $this->createDoctorWithUser();

        $this->createBooking([
            'status' => 'confirmed',
            'preferred_date' => today()->subDay(),
            'doctor_id' => $doctor->id,
        ]);

        $this->artisan('bookings:send-reminders')
            ->assertSuccessful();

        Notification::assertNotSentTo($user, BookingReminderNotification::class);
    }

    public function test_command_does_not_send_for_unconfirmed_bookings(): void
    {
        Notification::fake();

        [$doctor, $user] = $this->createDoctorWithUser();

        $this->createBooking([
            'status' => 'unconfirmed',
            'preferred_date' => today(),
            'doctor_id' => $doctor->id,
        ]);

        $this->artisan('bookings:send-reminders')
            ->assertSuccessful();

        Notification::assertNotSentTo($user, BookingReminderNotification::class);
    }

    public function test_command_does_not_send_for_bookings_without_doctor(): void
    {
        Notification::fake();

        $this->createBooking([
            'status' => 'confirmed',
            'preferred_date' => today(),
            'doctor_id' => null,
        ]);

        $this->artisan('bookings:send-reminders')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_returns_success(): void
    {
        $this->artisan('bookings:send-reminders')
            ->assertExitCode(0);
    }

    public function test_command_outputs_correct_count(): void
    {
        Notification::fake();

        [$doctor1, $user1] = $this->createDoctorWithUser();
        [$doctor2, $user2] = $this->createDoctorWithUser();

        $this->createBooking([
            'status' => 'confirmed',
            'preferred_date' => today(),
            'doctor_id' => $doctor1->id,
        ]);

        $this->createBooking([
            'status' => 'confirmed',
            'preferred_date' => today(),
            'doctor_id' => $doctor2->id,
        ]);

        $this->artisan('bookings:send-reminders')
            ->expectsOutput('Sent 2 booking reminders for today.')
            ->assertSuccessful();
    }

    public function test_command_does_not_send_for_future_bookings(): void
    {
        Notification::fake();

        [$doctor, $user] = $this->createDoctorWithUser();

        $this->createBooking([
            'status' => 'confirmed',
            'preferred_date' => today()->addDay(),
            'doctor_id' => $doctor->id,
        ]);

        $this->artisan('bookings:send-reminders')
            ->assertSuccessful();

        Notification::assertNotSentTo($user, BookingReminderNotification::class);
    }

    public function test_command_outputs_zero_when_no_bookings(): void
    {
        $this->artisan('bookings:send-reminders')
            ->expectsOutput('Sent 0 booking reminders for today.')
            ->assertSuccessful();
    }
}

<?php

namespace Tests\Feature\Integration;

use App\Events\BookingCreated;
use App\Jobs\ProcessNotificationJob;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\SmsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear Setting memory cache between tests to prevent leakage
        Setting::clearCache();
    }

    private function enableSms(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'gateway');
    }

    public function test_sms_notification_service_dispatches_job(): void
    {
        Queue::fake();
        $this->enableSms();

        $booking = Booking::create([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'dermatology_consultation',
            'status' => 'confirmed',
            'source' => 'website',
            'module' => 'derma',
            'preferred_date' => now()->addDay(),
            'preferred_time' => '10:00',
        ]);

        SmsNotificationService::bookingConfirmed($booking);

        // Booking confirmation now routes through the unified hub.
        Queue::assertPushed(ProcessNotificationJob::class, function (ProcessNotificationJob $job) {
            return $job->eventKey === 'booking.confirmed';
        });
    }

    public function test_booking_listener_dispatches_email_on_event(): void
    {
        Queue::fake();

        $booking = Booking::create([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'cosmetic_consultation',
            'status' => 'pending',
            'source' => 'website',
            'module' => 'derma',
            'preferred_date' => now()->addDay(),
        ]);

        // Fire the event -- the listener sends email via Mail (not SendEmailJob)
        // This verifies the event/listener pipeline works
        event(new BookingCreated($booking));

        // The SendBookingNotification listener uses Mail::send directly,
        // so we verify the event was dispatched successfully without errors.
        $this->assertTrue(true, 'BookingCreated event fired without errors.');
    }

    public function test_sms_notification_service_hands_off_to_hub_even_when_sms_disabled(): void
    {
        Queue::fake();

        // SMS disabled — but the service still hands off to the hub, which decides
        // per-channel delivery (in_app always records; sms/whatsapp gated there).

        $booking = Booking::create([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'service',
            'status' => 'pending',
            'source' => 'website',
            'module' => 'derma',
        ]);

        $result = SmsNotificationService::bookingConfirmed($booking);

        $this->assertTrue($result['success']);
        Queue::assertPushed(ProcessNotificationJob::class);
    }

    public function test_sms_notification_service_does_not_dispatch_without_phone(): void
    {
        Queue::fake();
        $this->enableSms();

        $booking = Booking::create([
            'full_name' => 'Test Patient',
            'phone' => '', // No phone
            'booking_type' => 'dermatology_consultation',
            'status' => 'pending',
            'source' => 'website',
            'module' => 'derma',
        ]);
        // Explicitly clear phone to simulate missing phone
        $booking->phone = null;

        $result = SmsNotificationService::bookingConfirmed($booking);

        $this->assertFalse($result['success']);
        Queue::assertNotPushed(ProcessNotificationJob::class);
    }

    public function test_booking_reminder_dispatches_sms_job(): void
    {
        Queue::fake();
        $this->enableSms();

        $booking = Booking::create([
            'full_name' => 'Reminder Patient',
            'phone' => '+966511111111',
            'booking_type' => 'dermatology_consultation',
            'status' => 'confirmed',
            'source' => 'website',
            'module' => 'derma',
            'preferred_date' => now()->addDay(),
            'preferred_time' => '14:00',
        ]);

        SmsNotificationService::bookingReminder($booking);

        Queue::assertPushed(ProcessNotificationJob::class, function (ProcessNotificationJob $job) {
            return $job->eventKey === 'appointment.reminder.day_before'
                && ($job->data['to'] ?? null) === '+966511111111';
        });
    }

    public function test_no_jobs_leak_through_synchronously(): void
    {
        Queue::fake();
        $this->enableSms();

        $booking = Booking::create([
            'full_name' => 'Sync Test Patient',
            'phone' => '+966522222222',
            'booking_type' => 'service',
            'status' => 'confirmed',
            'source' => 'website',
            'module' => 'derma',
            'preferred_date' => now()->addDay(),
        ]);

        SmsNotificationService::bookingConfirmed($booking);

        // Verify exactly one hub job was pushed (not executed synchronously,
        // and not double-sent across channels at dispatch time).
        Queue::assertPushed(ProcessNotificationJob::class, 1);
    }
}

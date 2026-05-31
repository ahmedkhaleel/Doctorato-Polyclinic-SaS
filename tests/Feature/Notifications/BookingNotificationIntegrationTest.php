<?php

namespace Tests\Feature\Notifications;

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\SmsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** Legacy SMS enabled, but the hub SMS channel toggle left OFF (back-compat path). */
    private function legacySmsEnabled(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Booking Patient', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-BNI-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs);
        $p->save();

        return $p;
    }

    private function booking(Patient $patient, array $attrs = []): Booking
    {
        return Booking::create(array_merge([
            'patient_id' => $patient->id,
            'booking_number' => 'BK-'.uniqid(),
            'module' => 'dental',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'phone' => $patient->phone,
            'full_name' => $patient->full_name,
        ], $attrs));
    }

    public function test_booking_confirmation_routes_through_hub_and_sends_sms(): void
    {
        $this->legacySmsEnabled(); // hub SMS channel still disabled
        $patient = $this->patient();
        $booking = $this->booking($patient);

        SmsNotificationService::bookingConfirmed($booking);

        $log = NotificationLog::where('event_key', 'booking.confirmed')->where('channel', 'sms')->first();
        $this->assertNotNull($log, 'expected an SMS hub log (legacy back-compat)');
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
        $this->assertSame($patient->id, $log->recipient_id);
        // Rendered from the enriched template with the booking variables.
        $this->assertStringContainsString($booking->booking_number, $log->meta['body']);
        // in_app always records too.
        $this->assertTrue(NotificationLog::where('event_key', 'booking.confirmed')->where('channel', 'in_app')->exists());
    }

    public function test_confirmation_sends_even_if_patient_opted_out_of_bookings(): void
    {
        // booking.confirmed is transactional → consent bypassed.
        $this->legacySmsEnabled();
        $patient = $this->patient(['notify_sms_bookings' => false]);
        $booking = $this->booking($patient);

        SmsNotificationService::bookingConfirmed($booking);

        $log = NotificationLog::where('event_key', 'booking.confirmed')->where('channel', 'sms')->first();
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
    }

    public function test_day_before_reminder_respects_sms_consent(): void
    {
        $this->legacySmsEnabled();
        // Opt out of SMS reminders; whatsapp disabled → reminder should be skipped on sms.
        $patient = $this->patient(['notify_sms_reminders' => false]);
        $booking = $this->booking($patient);

        SmsNotificationService::bookingReminder($booking);

        $log = NotificationLog::where('event_key', 'appointment.reminder.day_before')->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationLog::STATUS_SKIPPED, $log->status);
        $this->assertSame('no consent', $log->error);
    }

    public function test_reminder_dedup_prevents_double_send(): void
    {
        $this->legacySmsEnabled();
        $patient = $this->patient();
        $booking = $this->booking($patient);

        SmsNotificationService::sameDayReminder($booking);
        SmsNotificationService::sameDayReminder($booking); // same booking → deduped

        $sent = NotificationLog::where('event_key', 'appointment.reminder.same_day')
            ->where('channel', 'sms')->where('status', NotificationLog::STATUS_SENT)->count();
        $skipped = NotificationLog::where('event_key', 'appointment.reminder.same_day')
            ->where('channel', 'sms')->where('status', NotificationLog::STATUS_SKIPPED)->count();

        $this->assertSame(1, $sent);
        $this->assertSame(1, $skipped);
    }

    public function test_no_send_when_sms_disabled_and_no_whatsapp(): void
    {
        // Neither legacy SMS nor hub channels configured → only in_app logs.
        Setting::set('sms_enabled', '0');
        Setting::set('sms_provider', 'none');
        $patient = $this->patient();
        $booking = $this->booking($patient);

        SmsNotificationService::bookingConfirmed($booking);

        $this->assertFalse(NotificationLog::where('event_key', 'booking.confirmed')->where('channel', 'sms')->exists());
        $this->assertTrue(NotificationLog::where('event_key', 'booking.confirmed')->where('channel', 'in_app')->exists());
    }
}

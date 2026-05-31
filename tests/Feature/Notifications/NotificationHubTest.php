<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationChannelRoute;
use App\Models\NotificationEvent;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationHubTest extends TestCase
{
    use RefreshDatabase;

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Test Patient', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-NOTIF-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs); // allow notify_* + email
        $p->save();

        return $p;
    }

    private function enableChannel(string $channel, array $extra = []): void
    {
        NotificationChannel::where('channel', $channel)->update(array_merge(['enabled' => true], $extra));
    }

    /** Configure the SMS provider to Twilio + fake the HTTP call as a success. */
    private function fakeSms(): void
    {
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC_test');
        Setting::set('sms_twilio_auth_token', 'token_test');
        Setting::set('sms_twilio_from_number', '+10000000000');
        Http::fake(['*' => Http::response(['sid' => 'SM_test'], 201)]);
    }

    public function test_seed_migration_populated_catalogue(): void
    {
        $this->assertSame(4, NotificationChannel::count());
        $this->assertSame(21, NotificationEvent::count());
        $this->assertGreaterThanOrEqual(40, NotificationChannelRoute::count());
    }

    public function test_routes_event_to_in_app_and_logs_it(): void
    {
        // in_app is enabled by the seed migration.
        $patient = $this->patient();

        $logs = Notifier::eventNow('payment.received', $patient, ['body' => 'تم استلام الدفع']);

        $inApp = collect($logs)->firstWhere('channel', 'in_app');
        $this->assertNotNull($inApp);
        $this->assertSame(NotificationLog::STATUS_SENT, $inApp->status);
        $this->assertSame('payment.received', $inApp->event_key);
        $this->assertSame($patient->id, $inApp->recipient_id);
    }

    public function test_disabled_external_channels_are_skipped(): void
    {
        // sms/whatsapp/email all disabled by default → only in_app records.
        $patient = $this->patient();

        $logs = Notifier::eventNow('payment.received', $patient, ['body' => 'hi']);

        $channels = collect($logs)->pluck('channel')->all();
        $this->assertContains('in_app', $channels);
        $this->assertNotContains('sms', $channels);
    }

    public function test_sms_sends_when_channel_enabled(): void
    {
        $this->fakeSms();
        $this->enableChannel('sms');

        $patient = $this->patient(['phone' => '01098765432']);

        $logs = Notifier::eventNow('payment.received', $patient, ['body' => 'دفعة']);

        $sms = collect($logs)->firstWhere('channel', 'sms');
        $this->assertNotNull($sms, 'expected an SMS log');
        $this->assertSame(NotificationLog::STATUS_SENT, $sms->status);
        $this->assertSame('01098765432', $sms->to);
    }

    public function test_external_fallback_stops_after_first_success(): void
    {
        // Route an event to sms then email; enable both. SMS succeeds → email skipped.
        $this->fakeSms();
        Mail::fake();
        $this->enableChannel('sms');
        $this->enableChannel('email');

        // invoice.overdue → sms, email, in_app
        $patient = $this->patient(['email' => 'p@example.test', 'phone' => '01055554444']);

        $logs = Notifier::eventNow('invoice.overdue', $patient, ['body' => 'فاتورة متأخرة', 'subject' => 'Overdue']);

        $byChannel = collect($logs)->keyBy('channel');
        $this->assertSame(NotificationLog::STATUS_SENT, $byChannel['sms']->status);
        $this->assertArrayNotHasKey('email', $byChannel->all(), 'email should be skipped after sms success');
        Mail::assertNothingSent();
    }

    public function test_consent_blocks_marketing_sms_when_opted_out(): void
    {
        $this->fakeSms();
        $this->enableChannel('sms');

        $patient = $this->patient(['phone' => '01011112222', 'notify_sms_marketing' => false]);

        // lead.reactivation is a marketing event → sms
        $logs = Notifier::eventNow('lead.reactivation', $patient, ['body' => 'عرض']);

        $sms = collect($logs)->firstWhere('channel', 'sms');
        $this->assertNotNull($sms);
        $this->assertSame(NotificationLog::STATUS_SKIPPED, $sms->status);
        $this->assertSame('no consent', $sms->error);
    }

    public function test_transactional_ignores_consent(): void
    {
        $this->fakeSms();
        $this->enableChannel('sms');

        // Opt out of everything; transactional must still send.
        $patient = $this->patient([
            'phone' => '01033334444',
            'notify_sms_bookings' => false,
            'notify_sms_reminders' => false,
            'notify_sms_marketing' => false,
        ]);

        $logs = Notifier::eventNow('booking.confirmed', $patient, ['body' => 'تم الحجز']);

        $sms = collect($logs)->firstWhere('channel', 'sms');
        $this->assertSame(NotificationLog::STATUS_SENT, $sms->status);
    }

    public function test_daily_cap_blocks_further_sends(): void
    {
        $this->fakeSms();
        $this->enableChannel('sms', ['daily_cap' => 1]);

        $patient = $this->patient(['phone' => '01044445555']);

        $first = Notifier::eventNow('payment.received', $patient, ['body' => 'one']);
        $second = Notifier::eventNow('payment.received', $patient, ['body' => 'two']);

        $this->assertSame(NotificationLog::STATUS_SENT, collect($first)->firstWhere('channel', 'sms')->status);
        $blocked = collect($second)->firstWhere('channel', 'sms');
        $this->assertSame(NotificationLog::STATUS_SKIPPED, $blocked->status);
        $this->assertStringContainsString('cap', $blocked->error);
    }

    public function test_dedup_prevents_duplicate_sends(): void
    {
        $this->fakeSms();
        $this->enableChannel('sms');

        $patient = $this->patient(['phone' => '01066667777']);
        $key = 'appointment.reminder.same_day:booking:42';

        $first = Notifier::eventNow('appointment.reminder.same_day', $patient, ['body' => 'تذكير', 'dedup_key' => $key]);
        $second = Notifier::eventNow('appointment.reminder.same_day', $patient, ['body' => 'تذكير', 'dedup_key' => $key]);

        $this->assertSame(NotificationLog::STATUS_SENT, collect($first)->firstWhere('channel', 'sms')->status);
        $this->assertSame(NotificationLog::STATUS_SKIPPED, collect($second)->firstWhere('channel', 'sms')->status);
    }

    public function test_template_is_rendered_with_variables(): void
    {
        NotificationTemplate::create([
            'event_key' => 'payment.received',
            'channel' => 'in_app',
            'body_ar' => 'مرحبا {{name}}، تم استلام {{amount}} جنيه',
            'body_en' => 'Hi {{name}}, received {{amount}} EGP',
            'is_active' => true,
        ]);

        $patient = $this->patient(['preferred_language' => 'ar']);

        $logs = Notifier::eventNow('payment.received', $patient, ['name' => 'أحمد', 'amount' => 250, 'locale' => 'ar']);

        $inApp = collect($logs)->firstWhere('channel', 'in_app');
        $this->assertNotNull($inApp->template_id);
        // body lives on the message; assert via a fresh send capturing the rendered text is implicit,
        // so verify the template renders correctly in isolation:
        $rendered = NotificationTemplate::where('event_key', 'payment.received')->first()
            ->render('ar', ['name' => 'أحمد', 'amount' => 250]);
        $this->assertSame('مرحبا أحمد، تم استلام 250 جنيه', $rendered);
    }

    public function test_inactive_event_sends_nothing(): void
    {
        NotificationEvent::where('key', 'payment.received')->update(['is_active' => false]);
        $patient = $this->patient();

        $logs = Notifier::eventNow('payment.received', $patient, ['body' => 'x']);

        $this->assertSame([], $logs);
    }

    public function test_sms_cost_estimate_for_arabic_vs_ascii(): void
    {
        Setting::set('sms_cost_per_segment', '0.25');

        $this->assertSame(0.25, SmsService::estimateCost('Hello')); // 1 ascii segment
        $this->assertSame(0.25, SmsService::estimateCost('مرحبا'));  // 1 unicode segment
        // 80 arabic chars → 2 unicode segments (70/segment)
        $this->assertSame(0.5, SmsService::estimateCost(str_repeat('ا', 80)));
    }
}

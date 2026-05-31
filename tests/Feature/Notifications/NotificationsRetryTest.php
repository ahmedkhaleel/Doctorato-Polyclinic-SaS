<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationsRetryTest extends TestCase
{
    use RefreshDatabase;

    private function enableSmsTwilio(): void
    {
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
    }

    private function failedLog(array $attrs = []): NotificationLog
    {
        return NotificationLog::create(array_merge([
            'channel' => 'sms', 'event_key' => 'booking.confirmed', 'status' => 'failed',
            'to' => '201012345678', 'error' => 'timeout', 'meta' => ['body' => 'إعادة الإرسال'],
            'created_at' => now()->subMinutes(10),
        ], $attrs));
    }

    public function test_retry_resends_failed_log_and_marks_sent(): void
    {
        $this->enableSmsTwilio();
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
        $log = $this->failedLog();

        $this->artisan('notifications:retry')->assertExitCode(0);

        $log->refresh();
        $this->assertSame('sent', $log->status);
        $this->assertSame(1, $log->meta['retry_count']);
    }

    public function test_retry_skips_logs_over_max_attempts(): void
    {
        $this->enableSmsTwilio();
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
        $log = $this->failedLog(['meta' => ['body' => 'x', 'retry_count' => 3]]);

        $this->artisan('notifications:retry --max=3')->assertExitCode(0);

        $log->refresh();
        $this->assertSame('failed', $log->status); // untouched
    }

    public function test_retry_skips_logs_without_body(): void
    {
        $this->enableSmsTwilio();
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
        $log = $this->failedLog(['meta' => []]); // no body → cannot resend

        $this->artisan('notifications:retry')->assertExitCode(0);
        $this->assertSame('failed', $log->refresh()->status);
    }

    public function test_dry_run_does_not_send(): void
    {
        $this->enableSmsTwilio();
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
        $log = $this->failedLog();

        $this->artisan('notifications:retry --dry')->assertExitCode(0);

        $this->assertSame('failed', $log->refresh()->status);
        Http::assertNothingSent();
    }

    public function test_default_templates_were_seeded(): void
    {
        $this->assertTrue(NotificationTemplate::where('event_key', 'booking.confirmed')->where('channel', 'sms')->exists());
        $this->assertTrue(NotificationTemplate::where('event_key', 'payment.received')->where('channel', 'whatsapp')->exists());
        $this->assertGreaterThanOrEqual(20, NotificationTemplate::count());
    }

    public function test_integrity_check_flags_enabled_unconfigured_channel(): void
    {
        // Enable email but leave it without credentials.
        NotificationChannel::where('channel', 'email')->update(['enabled' => true]);

        \Artisan::call('data:integrity-check', ['--json' => true]);
        $output = \Artisan::output();

        $this->assertStringContainsString('notification_channel_enabled_unconfigured', $output);
    }

    public function test_integrity_check_flags_stuck_queued(): void
    {
        $log = NotificationLog::create([
            'channel' => 'sms', 'event_key' => 'booking.confirmed', 'status' => 'queued', 'to' => '20100',
        ]);
        // created_at isn't fillable → backdate via query builder.
        NotificationLog::where('id', $log->id)->update(['created_at' => now()->subHours(2)]);

        \Artisan::call('data:integrity-check', ['--json' => true]);
        $this->assertStringContainsString('notifications_stuck_queued', \Artisan::output());
    }
}

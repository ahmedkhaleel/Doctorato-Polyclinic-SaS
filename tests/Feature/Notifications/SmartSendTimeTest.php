<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\ScheduledNotification;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use App\Services\Notifications\SmartSendTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartSendTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function smsReady(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'SST', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-SST-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs)->save();

        return $p;
    }

    private function seedReadHistory(Patient $p, int $hour, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            NotificationLog::create([
                'recipient_type' => $p->getMorphClass(), 'recipient_id' => $p->id,
                'channel' => 'sms', 'event_key' => 'lead.welcome', 'status' => 'read',
                'read_at' => now()->subDays($i + 1)->setTime($hour, 0),
            ]);
        }
    }

    public function test_best_send_at_picks_modal_read_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 09:00:00'));
        $p = $this->patient();
        $this->seedReadHistory($p, 18, 4);

        $at = app(SmartSendTimeService::class)->bestSendAt($p);
        $this->assertSame('18:00', $at->format('H:i'));
    }

    public function test_insufficient_history_returns_null(): void
    {
        $p = $this->patient();
        $this->seedReadHistory($p, 18, 1); // < MIN_READS
        $this->assertNull(app(SmartSendTimeService::class)->bestSendAt($p));
    }

    public function test_marketing_deferred_to_best_hour_when_enabled(): void
    {
        $this->smsReady();
        Setting::set('notifications_smart_send_time', '1');
        Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00'));

        $p = $this->patient(['notify_sms_marketing' => true]);
        $this->seedReadHistory($p, 18, 4); // best hour 18:00 (future today)

        $logs = Notifier::eventNow('lead.reactivation', $p, ['body' => 'عرض']);

        $this->assertSame([], $logs); // deferred, not sent now
        $held = ScheduledNotification::where('reason', 'smart_time')->first();
        $this->assertNotNull($held);
        $this->assertSame('18:00', $held->send_after->format('H:i'));
    }

    public function test_transactional_not_affected_by_smart_time(): void
    {
        $this->smsReady();
        Setting::set('notifications_smart_send_time', '1');
        Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00'));

        $p = $this->patient();
        $this->seedReadHistory($p, 18, 4);

        $logs = Notifier::eventNow('payment.received', $p, ['body' => 'دفعة']);
        $this->assertSame(NotificationLog::STATUS_SENT, collect($logs)->firstWhere('channel', 'sms')->status);
        $this->assertSame(0, ScheduledNotification::where('reason', 'smart_time')->count());
    }

    public function test_disabled_sends_marketing_immediately(): void
    {
        $this->smsReady();
        Setting::set('notifications_smart_send_time', '0');
        $p = $this->patient(['notify_sms_marketing' => true]);
        $this->seedReadHistory($p, 18, 4);

        $logs = Notifier::eventNow('lead.reactivation', $p, ['body' => 'عرض']);
        $this->assertSame(NotificationLog::STATUS_SENT, collect($logs)->firstWhere('channel', 'sms')->status);
    }
}

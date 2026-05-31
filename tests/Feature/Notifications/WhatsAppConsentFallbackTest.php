<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppConsentFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'WA', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-WACF-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs);
        $p->save();

        return $p;
    }

    private function enableWhatsAppCloud(array $extra = []): void
    {
        $c = NotificationChannel::for('whatsapp');
        $c->enabled = true;
        $c->provider = 'cloud_api';
        $c->config = ['phone_number_id' => '1', 'access_token' => 't'];
        $c->forceFill($extra);
        $c->save();
    }

    private function enableSmsTwilio(): void
    {
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
    }

    public function test_whatsapp_marketing_blocked_when_opted_out(): void
    {
        $this->enableWhatsAppCloud();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]], 200)]);

        $patient = $this->patient(['notify_whatsapp_marketing' => false]);
        $logs = Notifier::eventNow('lead.reactivation', $patient, ['body' => 'عرض']);

        $wa = collect($logs)->firstWhere('channel', 'whatsapp');
        $this->assertSame(NotificationLog::STATUS_SKIPPED, $wa->status);
        $this->assertSame('no consent', $wa->error);
    }

    public function test_whatsapp_reminder_allowed_by_default(): void
    {
        $this->enableWhatsAppCloud();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]], 200)]);

        $patient = $this->patient(); // defaults: reminders ON
        $logs = Notifier::eventNow('appointment.reminder.same_day', $patient, ['body' => 'تذكير']);

        $wa = collect($logs)->firstWhere('channel', 'whatsapp');
        $this->assertSame(NotificationLog::STATUS_SENT, $wa->status);
    }

    public function test_fallback_to_sms_when_whatsapp_fails(): void
    {
        $this->enableWhatsAppCloud();
        $this->enableSmsTwilio();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'down']], 500),
            '*' => Http::response(['sid' => 'SM1'], 201), // twilio
        ]);

        // booking.confirmed → whatsapp (p0), sms (p1), in_app
        $patient = $this->patient(['phone' => '01055556666']);
        $logs = Notifier::eventNow('booking.confirmed', $patient, ['body' => 'تم الحجز']);

        $byChannel = collect($logs)->keyBy('channel');
        $this->assertSame(NotificationLog::STATUS_FAILED, $byChannel['whatsapp']->status);
        $this->assertSame(NotificationLog::STATUS_SENT, $byChannel['sms']->status);
    }

    public function test_whatsapp_daily_cap_enforced(): void
    {
        $this->enableWhatsAppCloud(['daily_cap' => 1]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]], 200)]);

        $patient = $this->patient();
        $first = Notifier::eventNow('booking.confirmed', $patient, ['body' => '1']);
        $second = Notifier::eventNow('booking.confirmed', $patient, ['body' => '2']);

        $this->assertSame(NotificationLog::STATUS_SENT, collect($first)->firstWhere('channel', 'whatsapp')->status);
        $this->assertSame(NotificationLog::STATUS_SKIPPED, collect($second)->firstWhere('channel', 'whatsapp')->status);
    }
}

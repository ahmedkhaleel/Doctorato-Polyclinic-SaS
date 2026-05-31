<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\Notifications\ChannelPreferenceService;
use App\Services\Notifications\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'Smart', 'phone' => '01012345678']);
        $p->file_number = 'P-SMART-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    private function history(Patient $p, string $channel, string $status, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            NotificationLog::create([
                'recipient_type' => $p->getMorphClass(), 'recipient_id' => $p->id,
                'channel' => $channel, 'event_key' => 'booking.confirmed', 'status' => $status,
            ]);
        }
    }

    public function test_ranker_orders_external_by_engagement_keeps_in_app(): void
    {
        $p = $this->patient();
        $this->history($p, 'sms', 'read', 3);       // score 9
        $this->history($p, 'whatsapp', 'failed', 2); // score -4

        $ordered = app(ChannelPreferenceService::class)->reorder($p, ['whatsapp', 'sms', 'in_app']);
        $this->assertSame(['sms', 'whatsapp', 'in_app'], $ordered);
    }

    public function test_no_history_keeps_original_order(): void
    {
        $p = $this->patient();
        $this->assertSame(['whatsapp', 'sms', 'in_app'],
            app(ChannelPreferenceService::class)->reorder($p, ['whatsapp', 'sms', 'in_app']));
    }

    private function enableBothChannels(): void
    {
        // WhatsApp cloud
        $wa = NotificationChannel::for('whatsapp');
        $wa->enabled = true;
        $wa->provider = 'cloud_api';
        $wa->config = ['phone_number_id' => '1', 'access_token' => 't'];
        $wa->save();
        // SMS twilio
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200),
            '*' => Http::response(['sid' => 'SM1'], 201), // twilio
        ]);
    }

    public function test_smart_routing_flips_fallback_to_preferred_channel(): void
    {
        Setting::set('notifications_smart_routing', '1');
        $this->enableBothChannels();

        $p = $this->patient();
        $this->history($p, 'sms', 'read', 3);        // prefers sms
        $this->history($p, 'whatsapp', 'failed', 2);

        // booking.confirmed routes whatsapp(0), sms(1), in_app — smart flips to sms-first.
        $logs = Notifier::eventNow('booking.confirmed', $p, ['body' => 'تم']);
        $byCh = collect($logs)->keyBy('channel');

        $this->assertSame(NotificationLog::STATUS_SENT, $byCh['sms']->status);
        // whatsapp not attempted this send (sms succeeded first) — no fresh queued/sent whatsapp log
        $freshWa = NotificationLog::where('recipient_id', $p->id)->where('channel', 'whatsapp')
            ->whereIn('status', ['sent', 'queued'])->exists();
        $this->assertFalse($freshWa);
    }

    public function test_without_smart_routing_uses_route_priority(): void
    {
        Setting::set('notifications_smart_routing', '0');
        $this->enableBothChannels();

        $p = $this->patient();
        $this->history($p, 'sms', 'read', 3); // would prefer sms, but smart is OFF

        $logs = Notifier::eventNow('booking.confirmed', $p, ['body' => 'تم']);
        $byCh = collect($logs)->keyBy('channel');

        // route priority → whatsapp first → whatsapp sent, sms skipped by fallback
        $this->assertSame(NotificationLog::STATUS_SENT, $byCh['whatsapp']->status);
        $this->assertArrayNotHasKey('sms', $byCh->all());
    }
}

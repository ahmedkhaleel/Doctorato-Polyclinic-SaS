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

class WhatsAppChannelTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'WA Patient', 'phone' => '01012345678']);
        $p->file_number = 'P-WA-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    /** config is an encrypted:array cast → must go through a model save, not query update. */
    private function configureWhatsApp(string $provider, array $config): void
    {
        $channel = NotificationChannel::for('whatsapp');
        $channel->enabled = true;
        $channel->provider = $provider;
        $channel->config = $config;
        $channel->save();
    }

    public function test_cloud_api_send_records_provider_ref(): void
    {
        $this->configureWhatsApp('cloud_api', [
            'phone_number_id' => '123456',
            'access_token' => 'EAA_test_token',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.HELLO']]], 200)]);

        $patient = $this->patient();
        $logs = Notifier::eventNow('booking.confirmed', $patient, ['body' => 'تم تأكيد حجزك']);

        $wa = collect($logs)->firstWhere('channel', 'whatsapp');
        $this->assertNotNull($wa);
        $this->assertSame(NotificationLog::STATUS_SENT, $wa->status);
        $this->assertSame('cloud_api', $wa->provider);
        $this->assertSame('wamid.HELLO', $wa->provider_ref);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/123456/messages')
            && $r['type'] === 'text');
    }

    public function test_cloud_api_template_payload_when_template_name_given(): void
    {
        $this->configureWhatsApp('cloud_api', ['phone_number_id' => '999', 'access_token' => 't']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.T']]], 200)]);

        $patient = $this->patient();
        Notifier::eventNow('booking.confirmed', $patient, [
            'body' => 'fallback',
            'meta' => ['template_name' => 'appointment_reminder', 'template_lang' => 'ar'],
        ]);

        Http::assertSent(fn ($r) => $r['type'] === 'template'
            && $r['template']['name'] === 'appointment_reminder');
    }

    public function test_bridge_send_succeeds(): void
    {
        $this->configureWhatsApp('bridge', [
            'base_url' => 'https://example.com',
            'api_key' => 'bridgekey',
        ]);
        Http::fake(['example.com/*' => Http::response(['success' => true, 'id' => 'br-1'], 200)]);

        $patient = $this->patient();
        $logs = Notifier::eventNow('booking.confirmed', $patient, ['body' => 'رسالة عبر الجسر']);

        $wa = collect($logs)->firstWhere('channel', 'whatsapp');
        $this->assertSame(NotificationLog::STATUS_SENT, $wa->status);
        $this->assertSame('bridge', $wa->provider);
        $this->assertSame('br-1', $wa->provider_ref);
    }

    public function test_disabled_whatsapp_is_skipped(): void
    {
        // whatsapp disabled by default → falls through (sms also off) to in_app only.
        $patient = $this->patient();
        $logs = Notifier::eventNow('booking.confirmed', $patient, ['body' => 'x']);

        $this->assertNull(collect($logs)->firstWhere('channel', 'whatsapp'));
        $this->assertNotNull(collect($logs)->firstWhere('channel', 'in_app'));
    }

    public function test_webhook_verification_echoes_challenge(): void
    {
        Setting::set('whatsapp_webhook_verify_token', 'secret-verify');

        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=secret-verify&hub.challenge=12345')
            ->assertOk()
            ->assertSee('12345');
    }

    public function test_webhook_verification_rejects_bad_token(): void
    {
        Setting::set('whatsapp_webhook_verify_token', 'secret-verify');

        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=WRONG&hub.challenge=12345')
            ->assertForbidden();
    }

    public function test_webhook_status_marks_log_delivered_then_read(): void
    {
        $log = NotificationLog::create([
            'channel' => 'whatsapp', 'provider' => 'cloud_api', 'provider_ref' => 'wamid.ABC',
            'event_key' => 'booking.confirmed', 'status' => NotificationLog::STATUS_SENT, 'to' => '201012345678',
        ]);

        $metaPayload = fn (string $status) => [
            'entry' => [['changes' => [['value' => ['statuses' => [
                ['id' => 'wamid.ABC', 'status' => $status],
            ]]]]]],
        ];

        $this->postJson('/webhooks/whatsapp', $metaPayload('delivered'))->assertOk();
        $log->refresh();
        $this->assertSame(NotificationLog::STATUS_DELIVERED, $log->status);
        $this->assertNotNull($log->delivered_at);

        $this->postJson('/webhooks/whatsapp', $metaPayload('read'))->assertOk();
        $log->refresh();
        $this->assertSame(NotificationLog::STATUS_READ, $log->status);
        $this->assertNotNull($log->read_at);

        // A late 'delivered' receipt must not downgrade a 'read' log.
        $this->postJson('/webhooks/whatsapp', $metaPayload('delivered'))->assertOk();
        $log->refresh();
        $this->assertSame(NotificationLog::STATUS_READ, $log->status);
    }

    public function test_webhook_records_inbound_message(): void
    {
        $payload = [
            'entry' => [['changes' => [['value' => [
                'statuses' => [['id' => 'x', 'status' => 'delivered']], // forces the meta branch
                'messages' => [[
                    'from' => '201099998888', 'id' => 'wamid.IN', 'type' => 'text',
                    'text' => ['body' => 'مرحبا، أريد حجز موعد'],
                ]],
            ]]]]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertOk();

        $inbound = NotificationLog::where('event_key', 'inbound.whatsapp')->first();
        $this->assertNotNull($inbound);
        $this->assertSame('201099998888', $inbound->to);
        $this->assertSame('inbound', $inbound->meta['direction']);
    }
}

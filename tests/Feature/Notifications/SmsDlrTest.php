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

class SmsDlrTest extends TestCase
{
    use RefreshDatabase;

    private function twilioReady(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
        Http::fake(['*' => Http::response(['sid' => 'SM_ABC'], 201)]);
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'DLR', 'phone' => '01012345678']);
        $p->file_number = 'P-DLR-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    private function smsLog(string $ref): NotificationLog
    {
        return NotificationLog::create([
            'channel' => 'sms', 'provider' => 'twilio', 'provider_ref' => $ref,
            'event_key' => 'booking.confirmed', 'status' => 'sent', 'to' => '201012345678',
        ]);
    }

    public function test_sms_send_captures_provider_ref(): void
    {
        $this->twilioReady();
        $patient = $this->patient();

        Notifier::eventNow('payment.received', $patient, ['body' => 'دفعة']);

        $log = NotificationLog::where('channel', 'sms')->where('event_key', 'payment.received')->first();
        $this->assertNotNull($log);
        $this->assertSame('SM_ABC', $log->provider_ref);
    }

    public function test_twilio_dlr_marks_delivered_then_read(): void
    {
        $log = $this->smsLog('SM_ABC');

        $this->post('/webhooks/sms/twilio', ['MessageSid' => 'SM_ABC', 'MessageStatus' => 'delivered'])->assertOk();
        $log->refresh();
        $this->assertSame('delivered', $log->status);
        $this->assertNotNull($log->delivered_at);

        $this->post('/webhooks/sms/twilio', ['MessageSid' => 'SM_ABC', 'MessageStatus' => 'read'])->assertOk();
        $this->assertSame('read', $log->refresh()->status);

        // Late "delivered" must not downgrade a "read" log.
        $this->post('/webhooks/sms/twilio', ['MessageSid' => 'SM_ABC', 'MessageStatus' => 'delivered'])->assertOk();
        $this->assertSame('read', $log->refresh()->status);
    }

    public function test_twilio_dlr_failure(): void
    {
        $log = $this->smsLog('SM_FAIL');
        $this->post('/webhooks/sms/twilio', ['MessageSid' => 'SM_FAIL', 'MessageStatus' => 'undelivered', 'ErrorCode' => '30008'])->assertOk();
        $log->refresh();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('30008', $log->error);
    }

    public function test_smsmisr_dlr_marks_delivered(): void
    {
        $log = $this->smsLog('SID-1');
        $this->post('/webhooks/sms/smsmisr', ['SMSID' => 'SID-1', 'DLRStatus' => 'DELIVRD'])->assertOk();
        $this->assertSame('delivered', $log->refresh()->status);
    }

    public function test_dlr_ignores_unknown_ref(): void
    {
        $log = $this->smsLog('REAL');
        $this->post('/webhooks/sms/twilio', ['MessageSid' => 'GHOST', 'MessageStatus' => 'delivered'])->assertOk();
        $this->assertSame('sent', $log->refresh()->status); // untouched
    }

    public function test_dlr_routes_are_csrf_exempt(): void
    {
        // No CSRF token supplied — should not 419.
        $this->post('/webhooks/sms/twilio', ['MessageSid' => 'X', 'MessageStatus' => 'delivered'])->assertOk();
    }
}

<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationConsent;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PreferenceCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function legacySms(): void
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
        $p = new Patient(array_merge(['full_name' => 'Pref', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-PREF-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs)->save();

        return $p;
    }

    // ── Quiet hours ─────────────────────────────────────
    public function test_quiet_hours_suppress_marketing_but_not_transactional(): void
    {
        $this->legacySms();
        Setting::set('notifications_quiet_start', '22:00');
        Setting::set('notifications_quiet_end', '08:00');
        Carbon::setTestNow(Carbon::parse('2026-06-01 23:30:00'));

        $patient = $this->patient(['notify_sms_marketing' => true]);

        $marketing = Notifier::eventNow('lead.reactivation', $patient, ['body' => 'عرض']);
        $this->assertSame(NotificationLog::STATUS_SKIPPED, collect($marketing)->firstWhere('channel', 'sms')->status);
        $this->assertSame('quiet hours', collect($marketing)->firstWhere('channel', 'sms')->error);

        $txn = Notifier::eventNow('payment.received', $patient, ['body' => 'دفعة']);
        $this->assertSame(NotificationLog::STATUS_SENT, collect($txn)->firstWhere('channel', 'sms')->status);
    }

    public function test_outside_quiet_hours_marketing_sends(): void
    {
        $this->legacySms();
        Setting::set('notifications_quiet_start', '22:00');
        Setting::set('notifications_quiet_end', '08:00');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $patient = $this->patient(['notify_sms_marketing' => true]);
        $logs = Notifier::eventNow('lead.reactivation', $patient, ['body' => 'عرض']);
        $this->assertSame(NotificationLog::STATUS_SENT, collect($logs)->firstWhere('channel', 'sms')->status);
    }

    // ── Frequency cap ───────────────────────────────────
    public function test_marketing_frequency_cap_enforced(): void
    {
        $this->legacySms();
        Setting::set('notifications_marketing_weekly_cap', '1');
        $patient = $this->patient(['notify_sms_marketing' => true]);

        $first = Notifier::eventNow('lead.reactivation', $patient, ['body' => '1']);
        $second = Notifier::eventNow('lead.welcome', $patient, ['body' => '2']);

        $this->assertSame(NotificationLog::STATUS_SENT, collect($first)->firstWhere('channel', 'sms')->status);
        $blocked = collect($second)->firstWhere('channel', 'sms');
        $this->assertSame(NotificationLog::STATUS_SKIPPED, $blocked->status);
        $this->assertSame('frequency cap', $blocked->error);
    }

    public function test_frequency_cap_does_not_affect_transactional(): void
    {
        $this->legacySms();
        Setting::set('notifications_marketing_weekly_cap', '1');
        $patient = $this->patient();

        Notifier::eventNow('payment.received', $patient, ['body' => '1']);
        $second = Notifier::eventNow('payment.received', $patient, ['body' => '2']);
        $this->assertSame(NotificationLog::STATUS_SENT, collect($second)->firstWhere('channel', 'sms')->status);
    }

    // ── STOP keyword ────────────────────────────────────
    public function test_stop_keyword_opts_patient_out_of_marketing(): void
    {
        // whatsapp channel must exist for provider lookup; not required enabled
        NotificationChannel::for('whatsapp');
        $patient = $this->patient([
            'notify_sms_marketing' => true,
            'notify_whatsapp_marketing' => true,
        ]);

        $payload = [
            'entry' => [['changes' => [['value' => [
                'statuses' => [['id' => 's1', 'status' => 'delivered']],
                'messages' => [['from' => '201012345678', 'id' => 'in1', 'type' => 'text', 'text' => ['body' => 'STOP']]],
            ]]]]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertOk();

        $patient->refresh();
        $this->assertFalse((bool) $patient->notify_sms_marketing);
        $this->assertFalse((bool) $patient->notify_whatsapp_marketing);
        $this->assertTrue(NotificationConsent::where('recipient_id', $patient->id)
            ->where('source', 'stop_keyword')->where('opted_in', false)->exists());
    }

    // ── Consent log ─────────────────────────────────────
    public function test_consent_log_records_changes_only(): void
    {
        $patient = $this->patient(['notify_sms_marketing' => false]);
        $patient->refresh(); // load DB defaults, as controllers do

        \App\Services\Notifications\ConsentService::sync($patient, [
            'notify_sms_marketing' => true,   // changed false→true → logged
            'notify_email_bookings' => true,  // unchanged (default true) → not logged
        ], 'patient_portal');

        $rows = NotificationConsent::where('recipient_id', $patient->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('sms', $rows->first()->channel);
        $this->assertSame('marketing', $rows->first()->category);
        $this->assertTrue($rows->first()->opted_in);
    }
}

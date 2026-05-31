<?php

namespace Tests\Feature\Notifications;

use App\Models\CommunicationTemplate;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrmHubLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function lead(array $attrs = []): Lead
    {
        return Lead::create(array_merge([
            'full_name' => 'Lead One', 'phone' => '01012345678', 'email' => 'lead@x.com', 'status' => 'new',
        ], $attrs));
    }

    private function template(string $channel = 'sms'): CommunicationTemplate
    {
        return CommunicationTemplate::create([
            'name' => 'Promo', 'channel' => $channel, 'category' => 'promotion',
            'subject' => 'Hi', 'body_ar' => 'مرحبا {{name}}', 'body_en' => 'Hi {{name}}',
            'variables' => ['name'], 'is_active' => true,
        ]);
    }

    public function test_crm_sms_send_is_mirrored_to_hub_log(): void
    {
        Queue::fake(); // SendSmsJob is dispatched by CRM; we only assert the hub log
        Setting::set('sms_cost_per_segment', '0.25');
        $lead = $this->lead();

        CommunicationService::send($lead, $this->template('sms'), 'sms', 'ar');

        $log = NotificationLog::where('event_key', 'crm.message')->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame($lead->id, $log->recipient_id);
        $this->assertSame('crm', $log->provider);
        $this->assertGreaterThan(0, (float) $log->cost); // sms cost estimated
    }

    public function test_crm_whatsapp_clicktochat_logged_as_sent(): void
    {
        $lead = $this->lead();
        CommunicationService::send($lead, $this->template('whatsapp'), 'whatsapp', 'ar');

        $log = NotificationLog::where('event_key', 'crm.message')->where('channel', 'whatsapp')->first();
        $this->assertSame('sent', $log->status);
        $this->assertSame(0.0, (float) $log->cost); // click-to-chat is free
    }

    public function test_crm_email_without_address_logged_as_failed(): void
    {
        $lead = $this->lead(['email' => null]);
        CommunicationService::send($lead, $this->template('email'), 'email', 'ar');

        $log = NotificationLog::where('event_key', 'crm.message')->where('channel', 'email')->first();
        $this->assertSame('failed', $log->status);
    }

    public function test_crm_logs_appear_in_analytics_scope(): void
    {
        Queue::fake();
        $lead = $this->lead();
        CommunicationService::send($lead, $this->template('sms'), 'sms', 'ar');

        // crm.message rows are visible to the unified Delivery Log / analytics.
        $this->assertSame(1, NotificationLog::where('event_key', 'crm.message')->count());
    }
}

<?php

namespace Tests\Feature\Crm;

use App\Jobs\SendCrmWebhook;
use App\Models\AiFeatureFlag;
use App\Models\CrmSetting;
use App\Models\FollowUpSequence;
use App\Models\FollowUpSequenceStep;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\NotificationCampaign;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\FollowUpAutomationService;
use App\Services\Notifications\CampaignService;
use App\Services\Notifications\LeadSegmentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CRM-3 — automation bridge: lead-audience hub campaigns, the send_ai_message
 * sequence action (AI + template fallback), and signed outbound webhooks.
 */
class CrmAutomationBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LeadSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => ['*'], 'is_system' => false]);
        $this->admin = User::create(['name' => 'Bridge Admin', 'email' => 'crm-bridge@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->source = LeadSource::create(['name_ar' => 'الموقع', 'name_en' => 'Website', 'slug' => 'website', 'is_active' => true]);
    }

    private function makeLead(array $overrides = []): Lead
    {
        static $i = 0;
        $i++;

        return Lead::create(array_merge([
            'full_name' => "Bridge Lead {$i}",
            'phone' => '20100000010'.$i,
            'lead_source_id' => $this->source->id,
            'status' => 'contacted',
            'priority' => 2,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    // ── Lead segment resolver ─────────────────────────────────────────

    public function test_lead_segment_excludes_converted_and_lost_by_default(): void
    {
        $this->makeLead(['status' => 'new']);
        $this->makeLead(['status' => 'converted']);
        $this->makeLead(['status' => 'lost']);

        $this->assertSame(1, (new LeadSegmentResolver)->count([]));
    }

    public function test_lead_segment_filters_by_status_priority_module(): void
    {
        $this->makeLead(['status' => 'qualified', 'priority' => 1, 'module' => 'derma']);
        $this->makeLead(['status' => 'new', 'priority' => 3, 'module' => 'dental']);

        $resolver = new LeadSegmentResolver;
        $this->assertSame(1, $resolver->count(['statuses' => ['qualified']]));
        $this->assertSame(1, $resolver->count(['priority' => 1]));
        $this->assertSame(1, $resolver->count(['module' => 'dental']));
        $this->assertSame(0, $resolver->count(['statuses' => ['qualified'], 'module' => 'dental']));
    }

    // ── Lead-audience hub campaign ────────────────────────────────────

    public function test_campaign_with_lead_audience_sends_and_logs_on_timeline(): void
    {
        Queue::fake(); // keep the hub's delivery jobs out of the way

        $lead = $this->makeLead(['status' => 'qualified']);
        $this->makeLead(['status' => 'converted']); // must NOT be targeted

        $campaign = NotificationCampaign::create([
            'name' => 'عرض الليزر', 'channel' => 'whatsapp',
            'body_ar' => 'عرض خاص لك', 'status' => 'draft',
            'rules' => ['audience' => 'leads', 'statuses' => ['qualified']],
            'created_by' => $this->admin->id,
        ]);

        $sent = app(CampaignService::class)->send($campaign);

        $this->assertSame(1, $sent);
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'whatsapp',
            'subject' => 'Campaign: عرض الليزر',
        ]);
        $this->assertSame('sent', $campaign->fresh()->status);
    }

    public function test_campaign_preview_counts_lead_audience(): void
    {
        $this->makeLead(['status' => 'new']);
        $this->makeLead(['status' => 'converted']);

        $this->actingAs($this->admin)
            ->postJson('/admin/notification-campaigns/preview', ['rules' => ['audience' => 'leads']])
            ->assertOk()->assertJson(['count' => 1]);
    }

    // ── send_ai_message sequence action ───────────────────────────────

    private function runStepAction(Lead $lead, FollowUpSequenceStep $step): array
    {
        $m = new \ReflectionMethod(FollowUpAutomationService::class, 'executeAction');

        return $m->invoke(null, $lead, $step);
    }

    private function makeAiStep(): FollowUpSequenceStep
    {
        $sequence = FollowUpSequence::create([
            'name' => 'AI Nurture', 'trigger_event' => 'manual', 'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        return FollowUpSequenceStep::create([
            'sequence_id' => $sequence->id, 'step_order' => 1, 'delay_minutes' => 0,
            'action_type' => FollowUpSequenceStep::ACTION_SEND_AI_MESSAGE,
            'message_ar' => 'رسالة احتياطية يا {{first_name}}', 'message_en' => 'Fallback message {{first_name}}',
            'is_active' => true,
        ]);
    }

    public function test_send_ai_message_falls_back_to_template_when_ai_off(): void
    {
        $lead = $this->makeLead();
        $step = $this->makeAiStep();

        $result = $this->runStepAction($lead, $step);

        $this->assertTrue($result['success']);
        // Fallback path = the plain sequence WhatsApp activity, not the AI one.
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'subject' => 'Auto WhatsApp (Sequence)']);
        $this->assertDatabaseMissing('lead_activities', ['lead_id' => $lead->id, 'subject' => 'Auto WhatsApp (Sequence, AI)']);
    }

    public function test_send_ai_message_uses_ai_draft_when_enabled(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_phi_redaction', '0', 'ai');
        AiFeatureFlag::create(['key' => 'lead_reply', 'enabled' => true, 'group' => 'wave1']);
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'مرحباً! هل ما زلت مهتماً بجلسات الليزر؟']]],
            'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 10],
        ], 200)]);

        $lead = $this->makeLead();
        $step = $this->makeAiStep();

        $result = $this->runStepAction($lead, $step);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'subject' => 'Auto WhatsApp (Sequence, AI)']);
    }

    // ── Signed outbound webhooks ──────────────────────────────────────

    public function test_no_webhook_when_disabled(): void
    {
        Queue::fake();

        $this->makeLead();

        Queue::assertNotPushed(SendCrmWebhook::class);
    }

    public function test_webhook_dispatched_on_create_status_change_and_convert(): void
    {
        CrmSetting::set('webhooks_enabled', true);
        CrmSetting::set('webhook_url', 'https://hooks.example.com/crm');
        Queue::fake();

        $lead = $this->makeLead(['status' => 'new']);
        Queue::assertPushed(SendCrmWebhook::class, fn ($job) => $job->event === 'lead.created' && $job->payload['lead']['id'] === $lead->id);

        $lead->update(['status' => 'qualified']);
        Queue::assertPushed(SendCrmWebhook::class, fn ($job) => $job->event === 'lead.status_changed'
            && $job->payload['previous_status'] === 'new');

        $lead->update(['status' => 'converted']);
        Queue::assertPushed(SendCrmWebhook::class, fn ($job) => $job->event === 'lead.converted');
    }

    public function test_webhook_delivery_is_signed_and_retries_on_failure(): void
    {
        CrmSetting::set('webhook_secret', 'top-secret');

        Http::fake(['hooks.example.com/*' => Http::response(['ok' => true], 200)]);

        $payload = ['event' => 'lead.created', 'lead' => ['id' => 1]];
        (new SendCrmWebhook('https://hooks.example.com/crm', 'lead.created', $payload))->handle();

        Http::assertSent(function ($request) use ($payload) {
            $expected = 'sha256='.hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), 'top-secret');

            return $request->url() === 'https://hooks.example.com/crm'
                && $request->header('X-Doctorato-Event')[0] === 'lead.created'
                && $request->header('X-Doctorato-Signature')[0] === $expected;
        });

        // Non-2xx throws → the queue retries (tries=3).
        Http::fake(['failing.example.com/*' => Http::response('nope', 500)]);
        $this->expectException(\RuntimeException::class);
        (new SendCrmWebhook('https://failing.example.com/crm', 'lead.created', $payload))->handle();
    }

    public function test_settings_accept_webhook_keys(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/crm-settings', [
                'webhooks_enabled' => true,
                'webhook_url' => 'https://hooks.example.com/crm',
                'webhook_secret' => 'abc123',
            ])
            ->assertRedirect();

        $this->assertSame('https://hooks.example.com/crm', CrmSetting::get('webhook_url'));
    }
}

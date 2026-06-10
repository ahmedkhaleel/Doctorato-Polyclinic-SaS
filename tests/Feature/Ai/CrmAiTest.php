<?php

namespace Tests\Feature\Ai;

use App\Console\Commands\CrmDormancyScan;
use App\Models\AiFeatureFlag;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * CRM-2 — AI features on the gated layer: lead summary, message draft,
 * bounded intent scoring, inbound triage, dormancy scan (AI + heuristic
 * fallback). All default OFF; blocked calls return a 422 reason, never 500.
 */
class CrmAiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => ['*'], 'is_system' => false]);
        $this->admin = User::create(['name' => 'CRM AI Admin', 'email' => 'crm-ai@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $source = LeadSource::create(['name_ar' => 'الموقع', 'name_en' => 'Website', 'slug' => 'website', 'is_active' => true]);
        $this->lead = Lead::create([
            'full_name' => 'AI Test Lead', 'phone' => '201000000077',
            'lead_source_id' => $source->id, 'status' => 'contacted',
            'priority' => 2, 'score' => 20, 'created_by' => $this->admin->id,
        ]);
    }

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_phi_redaction', '0', 'ai');
    }

    private function fakeChat(string $reply): void
    {
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => $reply]]],
            'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 15],
        ], 200)]);
    }

    // ── Gate behaviour ────────────────────────────────────────────────

    public function test_summary_blocked_when_ai_disabled(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/admin/crm/ai/leads/{$this->lead->id}/summary")
            ->assertStatus(422)->assertJson(['ok' => false, 'reason' => 'disabled']);
    }

    public function test_summary_blocked_when_feature_off(): void
    {
        $this->enableAi();

        $this->actingAs($this->admin)
            ->postJson("/admin/crm/ai/leads/{$this->lead->id}/summary")
            ->assertStatus(422)->assertJson(['ok' => false, 'reason' => 'feature_off']);
    }

    // ── Summary + draft ───────────────────────────────────────────────

    public function test_lead_summary_returns_text_and_logs_cost(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'crm_lead_summary', 'enabled' => true, 'group' => 'crm']);
        $this->fakeChat('Lead is warm. Next: call today, send price list.');

        $this->actingAs($this->admin)
            ->postJson("/admin/crm/ai/leads/{$this->lead->id}/summary")
            ->assertOk()->assertJson(['ok' => true, 'text' => 'Lead is warm. Next: call today, send price list.']);

        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'crm_lead_summary']);
    }

    public function test_message_draft_returns_text(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'lead_reply', 'enabled' => true, 'group' => 'wave1']);
        $this->fakeChat('مرحباً! يسعدنا تواصلك معنا بخصوص جلسات الليزر.');

        $this->actingAs($this->admin)
            ->postJson("/admin/crm/ai/leads/{$this->lead->id}/draft", ['channel' => 'whatsapp', 'tone' => 'friendly'])
            ->assertOk()->assertJson(['ok' => true]);
    }

    // ── Bounded intent scoring ────────────────────────────────────────

    public function test_intent_score_is_clamped_and_logged_as_activity(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'crm_intent_score', 'enabled' => true, 'group' => 'crm']);
        // Model tries +40 — must be clamped to +15.
        $this->fakeChat('{"adjustment": 40, "reason": "Asked for an appointment twice"}');

        $this->actingAs($this->admin)
            ->postJson("/admin/crm/ai/leads/{$this->lead->id}/score-intent")
            ->assertOk()->assertJson(['ok' => true, 'adjustment' => 15]);

        $this->assertSame(35, $this->lead->fresh()->score); // 20 + 15
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->lead->id,
            'subject' => 'AI intent score +15 → 35',
        ]);
    }

    public function test_intent_score_zero_creates_no_activity(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'crm_intent_score', 'enabled' => true, 'group' => 'crm']);
        $this->fakeChat('{"adjustment": 0, "reason": "No new signals"}');

        $before = $this->lead->activities()->count();

        $this->actingAs($this->admin)
            ->postJson("/admin/crm/ai/leads/{$this->lead->id}/score-intent")
            ->assertOk()->assertJson(['ok' => true, 'adjustment' => 0]);

        $this->assertSame(20, $this->lead->fresh()->score);
        $this->assertSame($before, $this->lead->activities()->count());
    }

    // ── Inbound triage ────────────────────────────────────────────────

    public function test_triage_classifies_inbound_message(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'crm_inbound_triage', 'enabled' => true, 'group' => 'crm']);
        $this->fakeChat('{"module":"dental","urgency":"high","priority":1,"summary":"Severe tooth pain, wants appointment today"}');

        $this->actingAs($this->admin)
            ->postJson('/admin/crm/ai/triage', ['message' => 'عندي ألم شديد في ضرسي وأريد موعداً اليوم'])
            ->assertOk()
            ->assertJson(['ok' => true, 'module' => 'dental', 'urgency' => 'high', 'priority' => 1]);
    }

    public function test_triage_sanitizes_invalid_model_output(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'crm_inbound_triage', 'enabled' => true, 'group' => 'crm']);
        $this->fakeChat('{"module":"surgery","urgency":"apocalyptic","priority":9,"summary":"x"}');

        $this->actingAs($this->admin)
            ->postJson('/admin/crm/ai/triage', ['message' => 'مرحبا'])
            ->assertOk()
            ->assertJson(['ok' => true, 'module' => null, 'urgency' => 'normal', 'priority' => 2]);
    }

    // ── Dormancy scan ─────────────────────────────────────────────────

    public function test_dormancy_scan_falls_back_to_heuristic_when_ai_off(): void
    {
        // Silent in-pipeline lead (no AI at all).
        $this->lead->update(['last_contacted_at' => now()->subDays(10)]);

        $this->artisan('crm:dormancy-scan')->assertSuccessful();

        $cached = Cache::get(CrmDormancyScan::CACHE_KEY);
        $this->assertSame('heuristic', $cached['mode']);
        $this->assertNotEmpty($cached['leads']);
        $this->assertSame($this->lead->id, $cached['leads'][0]['id']);
        $this->assertContains($cached['leads'][0]['risk'], ['high', 'medium', 'low']);
    }

    public function test_dormancy_scan_uses_ai_ranking_when_enabled(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'crm_dormancy_risk', 'enabled' => true, 'group' => 'crm']);
        $this->lead->update(['last_contacted_at' => now()->subDays(10)]);
        $this->fakeChat(json_encode([[
            'lead_id' => $this->lead->id, 'risk' => 'high', 'reason' => 'Engaged then silent for 10 days',
        ]]));

        $this->artisan('crm:dormancy-scan')->assertSuccessful();

        $cached = Cache::get(CrmDormancyScan::CACHE_KEY);
        $this->assertSame('ai', $cached['mode']);
        $this->assertSame('high', $cached['leads'][0]['risk']);
    }

    // ── UI gating prop ────────────────────────────────────────────────

    public function test_lead_show_exposes_enabled_crm_ai_features_only(): void
    {
        // AI off → empty list.
        $this->actingAs($this->admin)
            ->get("/admin/leads/{$this->lead->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('aiCrmFeatures', []));

        // AI on + one flag → only that key.
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'crm_lead_summary', 'enabled' => true, 'group' => 'crm']);

        $this->actingAs($this->admin)
            ->get("/admin/leads/{$this->lead->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('aiCrmFeatures', ['crm_lead_summary']));
    }
}

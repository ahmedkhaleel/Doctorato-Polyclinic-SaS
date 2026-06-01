<?php

namespace Tests\Feature\Ai;

use App\Models\AiConversation;
use App\Models\AiEmbedding;
use App\Models\AiFeatureFlag;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\Features\EmbeddingService;
use App\Services\Ai\Features\PatientAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPatientAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $perms = ['ai.view', 'ai.manage']): User
    {
        $r = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => $perms, 'is_system' => false]);

        return User::create(['name' => 'U', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_phi_redaction', '0', 'ai');
    }

    // ─── Cosine search ───────────────────────────────────────
    public function test_embedding_search_ranks_by_cosine(): void
    {
        $this->enableAi();
        AiEmbedding::create(['source' => 'faq', 'content' => 'Laser hair removal info', 'vector' => [1.0, 0.0, 0.0]]);
        AiEmbedding::create(['source' => 'faq', 'content' => 'Pediatric vaccination schedule', 'vector' => [0.0, 1.0, 0.0]]);

        // Query embedding aligns with the first vector.
        Http::fake(['*/embeddings' => Http::response(['data' => [['embedding' => [0.9, 0.1, 0.0]]]], 200)]);

        $results = app(EmbeddingService::class)->search('laser', 1);
        $this->assertSame(['Laser hair removal info'], $results);
    }

    // ─── PatientAssistant ask ────────────────────────────────
    public function test_patient_assistant_answers_and_logs_conversation(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'patient_assistant', 'enabled' => true, 'group' => 'patient']);

        Http::fake([
            '*/chat/completions' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'نقدّم خدمات الجلدية والليزر. يمكنك الحجز عبر البوابة.']]],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 15],
            ], 200),
        ]);

        $result = app(PatientAssistant::class)->ask('ما هي خدماتكم؟', 'sess-1', ['locale' => 'ar']);

        $this->assertStringContainsString('الجلدية', $result->text);
        $this->assertSame(2, AiConversation::where('session_id', 'sess-1')->count());
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'patient_assistant', 'status' => 'success']);
    }

    public function test_patient_assistant_blocked_when_feature_off(): void
    {
        $this->enableAi(); // flag not enabled
        $this->expectException(\App\Services\Ai\Exceptions\AiUnavailableException::class);
        app(PatientAssistant::class)->ask('test', 'sess-x');
    }

    // ─── Admin playground ────────────────────────────────────
    public function test_admin_playground_page_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/ai/patient-assistant')->assertOk();
    }

    public function test_admin_rebuild_with_empty_corpus_returns_zero(): void
    {
        $this->enableAi();
        // No faqs/services/doctors content rows in the corpus → 0 indexed, no embed calls.
        $this->actingAs($this->admin())->post('/admin/ai/patient-assistant/rebuild')->assertRedirect();
        $this->assertSame(0, AiEmbedding::count());
    }

    public function test_admin_test_endpoint_generates_answer(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'patient_assistant', 'enabled' => true, 'group' => 'patient']);
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'Hello, how can I help?']]],
            'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 6],
        ], 200)]);

        $this->actingAs($this->admin())->postJson('/admin/ai/patient-assistant/test', [
            'question' => 'hours?', 'session_id' => 'admin-playground',
        ])->assertOk()->assertJson(['ok' => true, 'text' => 'Hello, how can I help?']);
    }

    public function test_rebuild_requires_manage_permission(): void
    {
        $this->actingAs($this->admin(['ai.view']))->post('/admin/ai/patient-assistant/rebuild')->assertForbidden();
    }
}

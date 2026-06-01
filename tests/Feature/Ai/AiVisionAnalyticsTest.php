<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiVisionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['ai.doctor'], 'is_system' => true]);
        $role->update(['permissions' => ['ai.doctor']]);
        $this->doctorUser = User::create(['name' => 'V Doc', 'email' => 'v-doc@test.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $this->doctorUser->id, 'status' => 'active']);
    }

    private function admin(): User
    {
        $r = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => ['ai.view', 'ai.manage'], 'is_system' => false]);

        return User::create(['name' => 'U', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_phi_redaction', '0', 'ai');
    }

    public function test_vision_analyzes_dental_xray(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'dental_xray_vision', 'enabled' => true, 'group' => 'vision']);
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o',
            'choices' => [['message' => ['content' => 'Possible caries on tooth 16. Advisory.']]],
            'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 20],
        ], 200)]);

        $this->actingAs($this->doctorUser)->post('/doctor/ai/vision', [
            'mode' => 'dental_xray_vision',
            'image' => UploadedFile::fake()->image('xray.jpg', 200, 200),
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'dental_xray_vision', 'status' => 'success']);
    }

    public function test_transcription_returns_text(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'consult_transcription', 'enabled' => true, 'group' => 'vision']);
        Http::fake(['*/audio/transcriptions' => Http::response(['text' => 'المريض يشكو من صداع منذ يومين'], 200)]);

        $this->actingAs($this->doctorUser)->post('/doctor/ai/transcribe', [
            'audio' => UploadedFile::fake()->createWithContent('rec.mp3', str_repeat('A', 2048)),
        ])->assertOk()->assertJson(['ok' => true, 'text' => 'المريض يشكو من صداع منذ يومين']);

        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'consult_transcription']);
    }

    public function test_vision_blocked_when_feature_off(): void
    {
        $this->enableAi(); // dental_xray_vision flag not enabled
        $this->actingAs($this->doctorUser)->post('/doctor/ai/vision', [
            'mode' => 'dental_xray_vision',
            'image' => UploadedFile::fake()->image('x.jpg'),
        ])->assertStatus(422)->assertJson(['reason' => 'feature_off']);
    }

    public function test_admin_nl_analytics_answers_from_snapshot(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'nl_analytics', 'enabled' => true, 'group' => 'vision']);
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'Revenue exceeds expenses this month.']]],
            'usage' => ['prompt_tokens' => 40, 'completion_tokens' => 10],
        ], 200)]);

        $this->actingAs($this->admin())->postJson('/admin/ai/insights/ask', [
            'question' => 'How is revenue this month?',
        ])->assertOk()->assertJson(['ok' => true, 'text' => 'Revenue exceeds expenses this month.']);
    }

    public function test_insights_page_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/ai/insights')->assertOk();
    }
}

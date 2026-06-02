<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiPrescriptionEmbedTest extends TestCase
{
    use RefreshDatabase;

    private function doctorUser(array $perms = ['ai.doctor', 'prescriptions.view', 'prescriptions.create']): User
    {
        $role = Role::firstOrCreate(['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => $perms, 'is_system' => true]);
        $role->update(['permissions' => $perms]);
        $u = User::create(['name' => 'Doc', 'email' => 'doc'.uniqid().'@t.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $u->id, 'status' => 'active']);

        return $u;
    }

    public function test_ai_buttons_hidden_when_disabled(): void
    {
        $this->actingAs($this->doctorUser())->get('/doctor/prescriptions')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('aiRx.suggest', false)->where('aiRx.drugCheck', false));
    }

    public function test_ai_buttons_shown_when_enabled_and_permitted(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        AiFeatureFlag::create(['key' => 'prescription_suggest', 'enabled' => true, 'group' => 'clinical']);
        AiFeatureFlag::create(['key' => 'drug_interaction', 'enabled' => true, 'group' => 'clinical']);

        $this->actingAs($this->doctorUser())->get('/doctor/prescriptions')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('aiRx.suggest', true)->where('aiRx.drugCheck', true));
    }

    public function test_ai_buttons_hidden_without_ai_doctor_permission(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        AiFeatureFlag::create(['key' => 'prescription_suggest', 'enabled' => true, 'group' => 'clinical']);

        // doctor without ai.doctor permission
        $this->actingAs($this->doctorUser(['prescriptions.view', 'prescriptions.create']))->get('/doctor/prescriptions')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('aiRx.suggest', false));
    }
}

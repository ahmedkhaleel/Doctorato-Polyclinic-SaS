<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP7 — the np_note_assist AI endpoint is wired and degrades gracefully (422,
 * never 500) when AI is off, like the other clinical AI features.
 */
class Np7AiNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_np_note_endpoint_degrades_gracefully_when_ai_off(): void
    {
        ModuleManager::flushStaticCache();
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]);
        $role->update(['permissions' => ['ai.doctor']]);
        $user = User::create(['name' => 'Doc', 'email' => 'np7-ai@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $user->id, 'status' => 'active', 'module' => 'psychiatry']);

        $resp = $this->actingAs($user)->postJson('/doctor/ai/np-note', [
            'context' => 'MSE: mood depressed, affect constricted. Subjective: low mood 3 weeks.',
        ]);

        // AI is not configured in tests → graceful 422 with ok:false (never a 500).
        $resp->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_np_note_validates_input(): void
    {
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]);
        $role->update(['permissions' => ['ai.doctor']]);
        $user = User::create(['name' => 'Doc', 'email' => 'np7-ai2@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $user->id, 'status' => 'active', 'module' => 'psychiatry']);

        $this->actingAs($user)->postJson('/doctor/ai/np-note', [])->assertStatus(422);
    }
}

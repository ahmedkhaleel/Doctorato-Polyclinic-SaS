<?php

namespace Tests\Feature\Admin;

use App\Models\CommunicationTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommunicationTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-tpl@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_templates_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/templates')->assertOk();
    }

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/templates/create')->assertOk();
    }

    public function test_can_create_template(): void
    {
        $this->actingAs($this->admin)->post('/admin/templates', [
            'name' => 'Welcome Template',
            'channel' => 'sms',
            'category' => 'welcome',
            'body_en' => 'Welcome {name}!',
            'body_ar' => 'مرحبا {name}!',
        ])->assertRedirect();

        $this->assertDatabaseHas('communication_templates', [
            'name' => 'Welcome Template',
            'channel' => 'sms',
            'category' => 'welcome',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_template_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/templates', [])
            ->assertSessionHasErrors(['name', 'channel', 'category', 'body_en', 'body_ar']);
    }

    public function test_invalid_channel_rejected(): void
    {
        $this->actingAs($this->admin)->post('/admin/templates', [
            'name' => 'Test',
            'channel' => 'telegram',
            'category' => 'welcome',
            'body_en' => 'Test',
            'body_ar' => 'تست',
        ])->assertSessionHasErrors('channel');
    }

    public function test_can_update_template(): void
    {
        $tpl = CommunicationTemplate::create([
            'name' => 'Old', 'channel' => 'sms', 'category' => 'welcome',
            'body_en' => 'Old body', 'body_ar' => 'قديم', 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/templates/{$tpl->id}", [
            'name' => 'Updated',
            'channel' => 'whatsapp',
            'category' => 'follow_up',
            'body_en' => 'New body',
            'body_ar' => 'جديد',
        ])->assertRedirect();

        $this->assertDatabaseHas('communication_templates', [
            'id' => $tpl->id, 'name' => 'Updated', 'channel' => 'whatsapp',
        ]);
    }

    public function test_can_delete_template(): void
    {
        $tpl = CommunicationTemplate::create([
            'name' => 'Delete', 'channel' => 'email', 'category' => 'custom',
            'body_en' => 'Del', 'body_ar' => 'حذف', 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/templates/{$tpl->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('communication_templates', ['id' => $tpl->id]);
    }

    public function test_can_filter_by_channel(): void
    {
        $this->actingAs($this->admin)->get('/admin/templates?channel=sms')->assertOk();
    }
}

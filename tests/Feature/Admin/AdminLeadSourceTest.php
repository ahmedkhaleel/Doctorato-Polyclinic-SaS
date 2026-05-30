<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadSourceTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-src@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_lead_sources_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/lead-sources')->assertOk();
    }

    public function test_can_create_lead_source(): void
    {
        $this->actingAs($this->admin)->post('/admin/lead-sources', [
            'name_en' => 'Instagram',
            'name_ar' => 'انستقرام',
            'slug' => 'instagram',
            'channel_type' => 'online',
            'color' => '#E1306C',
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_sources', [
            'name_en' => 'Instagram',
            'slug' => 'instagram',
            'channel_type' => 'online',
        ]);
    }

    public function test_lead_source_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/lead-sources', [])
            ->assertSessionHasErrors(['name_en', 'name_ar', 'slug', 'channel_type']);
    }

    public function test_slug_must_be_unique(): void
    {
        LeadSource::create([
            'name_en' => 'Existing', 'name_ar' => 'موجود',
            'slug' => 'existing', 'channel_type' => 'online', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/lead-sources', [
            'name_en' => 'Another', 'name_ar' => 'آخر',
            'slug' => 'existing', 'channel_type' => 'online',
        ])->assertSessionHasErrors('slug');
    }

    public function test_can_update_lead_source(): void
    {
        $source = LeadSource::create([
            'name_en' => 'Old', 'name_ar' => 'قديم',
            'slug' => 'old-source', 'channel_type' => 'online', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/lead-sources/{$source->id}", [
            'name_en' => 'Updated',
            'name_ar' => 'محدث',
            'slug' => 'updated-source',
            'channel_type' => 'referral',
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_sources', [
            'id' => $source->id, 'name_en' => 'Updated', 'channel_type' => 'referral',
        ]);
    }

    public function test_can_delete_lead_source_without_leads(): void
    {
        $source = LeadSource::create([
            'name_en' => 'Delete Me', 'name_ar' => 'حذف',
            'slug' => 'delete-me', 'channel_type' => 'offline', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/lead-sources/{$source->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('lead_sources', ['id' => $source->id]);
    }

    public function test_cannot_delete_lead_source_with_leads(): void
    {
        $source = LeadSource::create([
            'name_en' => 'Has Leads', 'name_ar' => 'لديه عملاء',
            'slug' => 'has-leads', 'channel_type' => 'online', 'is_active' => true,
        ]);

        Lead::create([
            'full_name' => 'Test Lead',
            'phone' => '0100000000',
            'lead_source_id' => $source->id,
            'status' => 'new',
            'priority' => 1,
        ]);

        $this->actingAs($this->admin)->post("/admin/lead-sources/{$source->id}/delete")->assertRedirect();
        // Should still exist because it has leads
        $this->assertDatabaseHas('lead_sources', ['id' => $source->id]);
    }
}

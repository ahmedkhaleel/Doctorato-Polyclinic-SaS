<?php

namespace Tests\Feature\Crm;

use App\Console\Commands\CrmDormancyScan;
use App\Models\CrmSetting;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\LeadSource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** CRM-4 — the /admin/crm/today prioritized work queue. */
class CrmTodayQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LeadSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => ['*'], 'is_system' => false]);
        $this->admin = User::create(['name' => 'Today Admin', 'email' => 'crm-today@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->source = LeadSource::create(['name_ar' => 'الموقع', 'name_en' => 'Website', 'slug' => 'website', 'is_active' => true]);
    }

    private function makeLead(array $overrides = []): Lead
    {
        static $i = 0;
        $i++;

        return Lead::create(array_merge([
            'full_name' => "Today Lead {$i}",
            'phone' => '20100000020'.$i,
            'lead_source_id' => $this->source->id,
            'status' => 'new',
            'priority' => 2,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_today_queue_buckets_are_correct(): void
    {
        CrmSetting::set('sla_response_target_minutes', 30);

        // SLA breach: new + uncontacted + older than target
        $breach = $this->makeLead();
        $breach->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        // Hot uncontacted (within SLA — created just now, hot priority)
        $hot = $this->makeLead(['priority' => Lead::PRIORITY_HOT, 'status' => 'contacted']);

        // Overdue follow-up
        $overdueLead = $this->makeLead(['status' => 'qualified']);
        $overdueFu = LeadFollowUp::create([
            'lead_id' => $overdueLead->id, 'type' => 'call', 'status' => 'pending',
            'scheduled_at' => now()->subHours(3), 'assigned_to' => $this->admin->id,
        ]);

        // Today's upcoming follow-up
        $todayLead = $this->makeLead(['status' => 'contacted']);
        LeadFollowUp::create([
            'lead_id' => $todayLead->id, 'type' => 'whatsapp', 'status' => 'pending',
            'scheduled_at' => now()->addHours(2), 'assigned_to' => $this->admin->id,
        ]);

        // Dormancy cache entry
        $dormant = $this->makeLead(['status' => 'negotiation']);
        Cache::put(CrmDormancyScan::CACHE_KEY, [
            'generated_at' => now()->toIso8601String(), 'mode' => 'heuristic',
            'leads' => [['id' => $dormant->id, 'full_name' => $dormant->full_name, 'phone' => $dormant->phone,
                'status' => $dormant->status, 'source' => 'Website', 'risk' => 'high', 'reason' => 'صامت']],
        ], now()->addDay());

        $this->actingAs($this->admin)
            ->get('/admin/crm/today')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/CRM/Today')
                ->where('slaTargetMinutes', 30)
                ->has('overdue', 1)
                ->where('overdue.0.id', $overdueFu->id)
                ->has('slaBreaches', 1)
                ->where('slaBreaches.0.id', $breach->id)
                ->has('hotUncontacted', 1)
                ->where('hotUncontacted.0.id', $hot->id)
                ->has('dormancyLeads', 1)
                ->where('dormancyLeads.0.id', $dormant->id)
                ->has('todayFollowUps', 1)
            );
    }

    public function test_dormancy_bucket_dedupes_leads_already_in_other_buckets(): void
    {
        // A hot uncontacted lead that ALSO appears in the dormancy cache must
        // show once (in the hot bucket), not twice.
        $hot = $this->makeLead(['priority' => Lead::PRIORITY_HOT, 'status' => 'contacted']);
        Cache::put(CrmDormancyScan::CACHE_KEY, [
            'generated_at' => now()->toIso8601String(), 'mode' => 'heuristic',
            'leads' => [['id' => $hot->id, 'full_name' => $hot->full_name, 'phone' => $hot->phone,
                'status' => $hot->status, 'source' => 'Website', 'risk' => 'high', 'reason' => 'x']],
        ], now()->addDay());

        $this->actingAs($this->admin)
            ->get('/admin/crm/today')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('hotUncontacted', 1)
                ->has('dormancyLeads', 0)
            );
    }

    public function test_completing_a_follow_up_from_the_queue(): void
    {
        $lead = $this->makeLead(['status' => 'qualified']);
        $fu = LeadFollowUp::create([
            'lead_id' => $lead->id, 'type' => 'call', 'status' => 'pending',
            'scheduled_at' => now()->subHour(), 'assigned_to' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/follow-ups/{$fu->id}/complete", [])
            ->assertRedirect();

        $this->assertSame('completed', $fu->fresh()->status);
    }

    public function test_requires_leads_view_permission(): void
    {
        // Admin role (passes AdminAuth) but WITHOUT leads.view.
        $role = Role::where('name', 'admin')->first();
        $role->update(['permissions' => ['bookings.view']]);
        $user = User::create(['name' => 'Limited', 'email' => 'limited-today@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->get('/admin/crm/today')->assertForbidden();
    }
}

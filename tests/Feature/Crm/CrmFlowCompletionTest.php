<?php

namespace Tests\Feature\Crm;

use App\Models\CommunicationTemplate;
use App\Models\ContactMessage;
use App\Models\CrmCampaign;
use App\Models\CrmSetting;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MarketerCommission;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\CommunicationService;
use App\Services\Crm\CrmRevenueService;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRM-1 — clinic flow completion: phone normalization in capture paths,
 * auto conversion commission (settings-gated), referral attribution,
 * real revenue attribution, SLA target wiring, template variables v2.
 */
class CrmFlowCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LeadSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'CRM Admin', 'email' => 'crm-flow@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->source = LeadSource::create([
            'name_ar' => 'الموقع', 'name_en' => 'Website', 'slug' => 'website', 'is_active' => true,
        ]);
    }

    private function makeLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'full_name' => 'Flow Lead',
            'phone' => '201000000099',
            'lead_source_id' => $this->source->id,
            'status' => 'new',
            'priority' => 2,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    // ── Phone normalization in capture paths ─────────────────────────

    public function test_contact_message_lead_gets_normalized_phone(): void
    {
        $message = ContactMessage::create([
            'name' => 'Web Visitor', 'phone' => '010 1234 5678',
            'subject' => 'سؤال', 'message' => 'استفسار عن الاسعار',
        ]);

        $lead = LeadService::createFromContactMessage($message);

        $this->assertNotNull($lead);
        $this->assertSame('201012345678', $lead->phone);
    }

    public function test_contact_message_dedupes_against_local_form_lead(): void
    {
        $existing = $this->makeLead(['phone' => '01012345678']); // stored unnormalized (legacy row)

        $message = ContactMessage::create([
            'name' => 'Same Person', 'phone' => '+201012345678',
            'subject' => 'متابعة', 'message' => 'نفس العميل برقم دولي',
        ]);

        $lead = LeadService::createFromContactMessage($message);

        $this->assertSame($existing->id, $lead->id);
        $this->assertSame(1, Lead::count());
    }

    // ── Auto conversion commission (settings-gated) ──────────────────

    public function test_no_commission_when_setting_off(): void
    {
        $lead = $this->makeLead(['assigned_to' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->post("/admin/leads/{$lead->id}/convert", [])
            ->assertRedirect();

        $this->assertSame('converted', $lead->fresh()->status);
        $this->assertSame(0, MarketerCommission::count());
    }

    public function test_fixed_commission_created_pending_on_conversion(): void
    {
        CrmSetting::set('auto_commission_enabled', true);
        CrmSetting::set('commission_type', 'fixed');
        CrmSetting::set('commission_rate', 150);

        $lead = $this->makeLead(['assigned_to' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->post("/admin/leads/{$lead->id}/convert", [])
            ->assertRedirect();

        $commission = MarketerCommission::where('lead_id', $lead->id)->first();
        $this->assertNotNull($commission);
        $this->assertSame('pending', $commission->status);
        $this->assertSame('fixed', $commission->commission_type);
        $this->assertEquals(150.0, (float) $commission->commission_amount);
        $this->assertSame($this->admin->id, $commission->user_id);
    }

    public function test_no_commission_for_unassigned_lead(): void
    {
        CrmSetting::set('auto_commission_enabled', true);
        CrmSetting::set('commission_type', 'fixed');
        CrmSetting::set('commission_rate', 150);

        $lead = $this->makeLead(['assigned_to' => null]);

        $this->actingAs($this->admin)
            ->post("/admin/leads/{$lead->id}/convert", [])
            ->assertRedirect();

        $this->assertSame(0, MarketerCommission::count());
    }

    public function test_commission_is_idempotent_per_lead(): void
    {
        CrmSetting::set('auto_commission_enabled', true);
        CrmSetting::set('commission_type', 'fixed');
        CrmSetting::set('commission_rate', 150);

        $lead = $this->makeLead(['assigned_to' => $this->admin->id]);

        // A manual commission already exists for this lead.
        MarketerCommission::create([
            'user_id' => $this->admin->id, 'lead_id' => $lead->id,
            'commission_type' => 'fixed', 'rate' => 50, 'base_amount' => 0,
            'commission_amount' => 50, 'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/leads/{$lead->id}/convert", [])
            ->assertRedirect();

        $this->assertSame(1, MarketerCommission::where('lead_id', $lead->id)->count());
    }

    public function test_percentage_commission_without_booking_is_skipped(): void
    {
        CrmSetting::set('auto_commission_enabled', true);
        CrmSetting::set('commission_type', 'percentage');
        CrmSetting::set('commission_rate', 10);

        $lead = $this->makeLead(['assigned_to' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->post("/admin/leads/{$lead->id}/convert", [])
            ->assertRedirect();

        // No invoice base → nothing to take a percentage of.
        $this->assertSame(0, MarketerCommission::count());
    }

    // ── Referral attribution ──────────────────────────────────────────

    public function test_referral_code_logs_referring_patient_activity(): void
    {
        $referrer = Patient::create([
            'full_name' => 'Referring Patient', 'phone' => '201000000001', 'is_active' => true,
        ]);
        $referrer->referral_code = 'REF-TEST-01';
        $referrer->save();

        $lead = $this->makeLead(['referral_code' => 'REF-TEST-01']);

        $this->actingAs($this->admin)
            ->post("/admin/leads/{$lead->id}/convert", [])
            ->assertRedirect();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'subject' => "Referred by patient #{$referrer->file_number} ({$referrer->full_name})",
        ]);
    }

    // ── Real revenue attribution ──────────────────────────────────────

    public function test_revenue_attribution_sums_converted_lead_patient_invoices(): void
    {
        $campaign = CrmCampaign::create([
            'name' => 'Summer Derma', 'status' => 'active',
            'actual_cost' => 500, 'created_by' => $this->admin->id,
        ]);

        $patient = Patient::create([
            'full_name' => 'Converted Patient', 'phone' => '201000000002', 'is_active' => true,
        ]);

        $lead = $this->makeLead([
            'status' => 'converted',
            'patient_id' => $patient->id,
            'campaign_id' => $campaign->id,
            'converted_at' => now(),
        ]);

        foreach ([300, 450] as $amount) {
            Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'patient_id' => $patient->id,
                'subtotal' => $amount,
                'total' => $amount,
                'created_by' => $this->admin->id,
            ]);
        }

        // Unrelated invoice (different patient, no lead) must NOT count.
        $other = Patient::create(['full_name' => 'Other', 'phone' => '201000000003', 'is_active' => true]);
        Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $other->id,
            'subtotal' => 999, 'total' => 999,
            'created_by' => $this->admin->id,
        ]);

        $service = new CrmRevenueService;

        $this->assertEquals(750.0, $service->total());

        $bySource = collect($service->bySource())->keyBy('lead_source_id');
        $this->assertEquals(750.0, $bySource[$this->source->id]['revenue']);
        $this->assertSame(1, $bySource[$this->source->id]['converted']);

        $roi = $service->campaignRoi($campaign->id);
        $this->assertEquals(750.0, $roi['revenue']);
        $this->assertEquals(500.0, $roi['cost']);
        $this->assertEquals(50.0, $roi['roi']); // (750-500)/500 = 50%
    }

    public function test_campaign_roi_is_null_when_no_cost(): void
    {
        $campaign = CrmCampaign::create([
            'name' => 'No-cost Campaign', 'status' => 'active', 'created_by' => $this->admin->id,
        ]);

        $roi = (new CrmRevenueService)->campaignRoi($campaign->id);
        $this->assertNull($roi['roi']);
    }

    // ── SLA target wiring ─────────────────────────────────────────────

    public function test_dashboard_sla_target_reads_crm_setting(): void
    {
        CrmSetting::set('sla_response_target_minutes', 30);

        $this->actingAs($this->admin)
            ->get('/admin/crm')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/CRM/Dashboard')
                ->where('slaMetrics.target_minutes', 30)
            );
    }

    // ── Settings: commission keys accepted ────────────────────────────

    public function test_settings_update_accepts_commission_keys(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/crm-settings', [
                'auto_commission_enabled' => true,
                'commission_type' => 'percentage',
                'commission_rate' => 12.5,
            ])
            ->assertRedirect();

        $this->assertTrue(filter_var(CrmSetting::get('auto_commission_enabled'), FILTER_VALIDATE_BOOLEAN));
        $this->assertSame('percentage', CrmSetting::get('commission_type'));
        $this->assertEquals(12.5, (float) CrmSetting::get('commission_rate'));
    }

    // ── Template rendering + variables v2 ─────────────────────────────

    public function test_render_body_supports_single_and_double_braces(): void
    {
        $template = CommunicationTemplate::create([
            'name' => 'Welcome', 'channel' => 'whatsapp', 'category' => 'welcome',
            'body_en' => 'Hi {name} — and hello {{name}}!',
            'body_ar' => 'مرحبا {name}',
            'is_active' => true,
        ]);

        $rendered = $template->renderBody('en', ['name' => 'Ahmed']);
        $this->assertSame('Hi Ahmed — and hello Ahmed!', $rendered);
    }

    public function test_build_variables_includes_v2_context(): void
    {
        $lead = $this->makeLead([
            'interested_services' => ['Laser', 'Filler'],
            'assigned_to' => $this->admin->id,
            'module' => 'derma',
            'next_follow_up_at' => now()->addDay(),
        ]);

        $vars = CommunicationService::buildVariables($lead->fresh());

        $this->assertSame('Laser، Filler', $vars['service']);
        $this->assertSame('الموقع', $vars['source']);
        $this->assertSame('CRM Admin', $vars['assigned_to']);
        $this->assertSame('الجلدية', $vars['module']);
        $this->assertNotSame('', $vars['next_follow_up']);
    }
}

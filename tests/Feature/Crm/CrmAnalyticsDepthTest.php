<?php

namespace Tests\Feature\Crm;

use App\Models\CrmSetting;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRM-5 — analytics depth on /admin/crm-reports: cohorts, SLA trend, revenue summary, AI cost card. */
class CrmAnalyticsDepthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LeadSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => ['*'], 'is_system' => false]);
        $this->admin = User::create(['name' => 'Analytics Admin', 'email' => 'crm-analytics@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->source = LeadSource::create(['name_ar' => 'الموقع', 'name_en' => 'Website', 'slug' => 'website', 'is_active' => true]);
    }

    public function test_reports_expose_crm5_props(): void
    {
        CrmSetting::set('sla_response_target_minutes', 45);

        $this->actingAs($this->admin)
            ->get('/admin/crm-reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/CRM/Reports')
                ->has('cohorts', 6)
                ->has('slaTrend', 8)
                ->where('slaTargetMinutes', 45)
                ->has('revenueSummary.total')
                ->has('revenueSummary.per_converted')
                ->has('aiCosts.calls')
                ->has('aiCosts.cost_usd')
            );
    }

    public function test_cohort_math_counts_conversions_lag_and_revenue(): void
    {
        $patient = Patient::create(['full_name' => 'Cohort Patient', 'phone' => '201000000301', 'is_active' => true]);

        // A lead created this month, converted after 4 days, with 500 in invoices.
        $lead = Lead::create([
            'full_name' => 'Cohort Lead', 'phone' => '201000000302',
            'lead_source_id' => $this->source->id, 'status' => 'converted',
            'priority' => 2, 'patient_id' => $patient->id, 'created_by' => $this->admin->id,
        ]);
        $lead->forceFill([
            'created_at' => now()->startOfMonth()->addDay(),
            'converted_at' => now()->startOfMonth()->addDays(5),
        ])->saveQuietly();

        Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'subtotal' => 500, 'total' => 500,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/crm-reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('cohorts.5.created', 1)       // current month is the last cohort
                ->where('cohorts.5.converted', 1)
                ->where('cohorts.5.rate', 100)
                ->where('cohorts.5.avg_days_to_convert', 4)
                ->where('cohorts.5.revenue', 500)
            );
    }

    public function test_sla_trend_marks_within_target_contacts(): void
    {
        CrmSetting::set('sla_response_target_minutes', 60);

        // Contacted within 30 minutes (within target) — created this week.
        $fast = Lead::create([
            'full_name' => 'Fast Lead', 'phone' => '201000000303',
            'lead_source_id' => $this->source->id, 'status' => 'contacted',
            'priority' => 2, 'created_by' => $this->admin->id,
        ]);
        $fast->forceFill([
            'created_at' => now()->startOfWeek()->addHours(9),
            'first_contacted_at' => now()->startOfWeek()->addHours(9)->addMinutes(30),
        ])->saveQuietly();

        // Contacted after 3 hours (breach) — same week.
        $slow = Lead::create([
            'full_name' => 'Slow Lead', 'phone' => '201000000304',
            'lead_source_id' => $this->source->id, 'status' => 'contacted',
            'priority' => 2, 'created_by' => $this->admin->id,
        ]);
        $slow->forceFill([
            'created_at' => now()->startOfWeek()->addHours(10),
            'first_contacted_at' => now()->startOfWeek()->addHours(13),
        ])->saveQuietly();

        $this->actingAs($this->admin)
            ->get('/admin/crm-reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('slaTrend.7.total', 2)   // current week is the last entry
                ->where('slaTrend.7.within', 1)
                ->where('slaTrend.7.pct', 50)
            );
    }
}

<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationCampaign;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\Notifications\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignAbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Patient::query()->forceDelete();
    }

    private function smsReady(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
    }

    private function patients(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $p = new Patient(['full_name' => "P{$i}", 'phone' => '0101'.str_pad((string) $i, 7, '0', STR_PAD_LEFT)]);
            $p->file_number = 'P-AB-'.uniqid();
            $p->is_active = true;
            $p->forceFill(['notify_sms_marketing' => true])->save();
        }
    }

    public function test_ab_campaign_splits_variants(): void
    {
        $this->smsReady();
        $this->patients(4);

        $campaign = NotificationCampaign::create([
            'name' => 'AB', 'channel' => 'sms', 'body_ar' => 'نسخة أ',
            'ab_enabled' => true, 'body_ar_b' => 'نسخة ب', 'rules' => [], 'status' => 'draft',
        ]);

        app(CampaignService::class)->send($campaign);

        $a = NotificationLog::where('campaign_id', $campaign->id)->where('ab_variant', 'A')->count();
        $b = NotificationLog::where('campaign_id', $campaign->id)->where('ab_variant', 'B')->count();
        $this->assertSame(2, $a);
        $this->assertSame(2, $b);
        // Variant B recipients got body B.
        $bLog = NotificationLog::where('campaign_id', $campaign->id)->where('ab_variant', 'B')->first();
        $this->assertSame('نسخة ب', $bLog->meta['body']);
    }

    public function test_non_ab_campaign_has_no_variant(): void
    {
        $this->smsReady();
        $this->patients(2);

        $campaign = NotificationCampaign::create([
            'name' => 'Plain', 'channel' => 'sms', 'body_ar' => 'عادي', 'rules' => [], 'status' => 'draft',
        ]);
        app(CampaignService::class)->send($campaign);

        $this->assertSame(2, NotificationLog::where('campaign_id', $campaign->id)->whereNull('ab_variant')->count());
    }

    public function test_ab_results_compute_read_rate_and_winner(): void
    {
        $campaign = NotificationCampaign::create([
            'name' => 'AB2', 'channel' => 'sms', 'body_ar' => 'أ',
            'ab_enabled' => true, 'body_ar_b' => 'ب', 'rules' => [], 'status' => 'sent',
        ]);

        // Variant A: 2 sent, 1 read (50%). Variant B: 2 sent, 2 read (100% → winner).
        $mk = fn ($variant, $status) => NotificationLog::create([
            'channel' => 'sms', 'event_key' => 'campaign.message', 'campaign_id' => $campaign->id,
            'ab_variant' => $variant, 'status' => $status,
        ]);
        $mk('A', 'sent');
        $mk('A', 'read');
        $mk('B', 'read');
        $mk('B', 'read');

        $role = \App\Models\Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => ['*'], 'is_system' => true]);
        $admin = \App\Models\User::create(['name' => 'A', 'email' => 'ab-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $res = $this->actingAs($admin)->get('/admin/notification-campaigns');
        $res->assertOk();
        $props = $res->original->getData()['page']['props'];

        $row = collect($props['campaigns'])->firstWhere('id', $campaign->id);
        $this->assertSame(50.0, $row['ab_results']['A']['read_rate']);
        $this->assertSame(100.0, $row['ab_results']['B']['read_rate']);
        $this->assertSame('B', $row['ab_results']['winner']);
    }
}

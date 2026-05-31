<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationCampaign;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notifications\CampaignService;
use App\Services\Notifications\SegmentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Remove any seed-migration demo patient so audience counts are deterministic.
        Patient::query()->forceDelete();
    }

    private function admin(array $perms = ['*']): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => $perms, 'is_system' => true]);
        $role->update(['permissions' => $perms]);

        return User::create(['name' => 'A', 'email' => 'cmp-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Seg', 'phone' => '0101'.random_int(1000000, 9999999)], $attrs));
        $p->file_number = 'P-CMP-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs)->save();

        return $p;
    }

    private function smsReady(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
    }

    public function test_segment_resolver_filters_by_gender_and_consent(): void
    {
        $this->patient(['gender' => 'female', 'notify_sms_marketing' => true]);
        $this->patient(['gender' => 'female', 'notify_sms_marketing' => false]);
        $this->patient(['gender' => 'male', 'notify_sms_marketing' => true]);

        $resolver = app(SegmentResolver::class);
        $this->assertSame(2, $resolver->count(['gender' => 'female']));
        $this->assertSame(1, $resolver->count(['gender' => 'female', 'marketing_channel' => 'sms']));
    }

    public function test_campaign_send_dispatches_to_audience_respecting_consent(): void
    {
        $this->smsReady();
        $optIn = $this->patient(['notify_sms_marketing' => true]);
        $optOut = $this->patient(['notify_sms_marketing' => false]);

        $campaign = NotificationCampaign::create([
            'name' => 'Promo', 'channel' => 'sms', 'body_ar' => 'عرض خاص', 'rules' => [], 'status' => 'draft',
        ]);

        $count = app(CampaignService::class)->send($campaign);
        $campaign->refresh();

        $this->assertSame('sent', $campaign->status);
        $this->assertSame(2, $count); // both dispatched
        // opted-in patient → sms sent; opted-out → skipped (no consent)
        $this->assertTrue(NotificationLog::where('event_key', 'campaign.message')->where('recipient_id', $optIn->id)->where('channel', 'sms')->where('status', 'sent')->exists());
        $this->assertTrue(NotificationLog::where('event_key', 'campaign.message')->where('recipient_id', $optOut->id)->where('channel', 'sms')->where('status', 'skipped')->exists());
    }

    public function test_store_send_now_creates_and_sends(): void
    {
        $this->smsReady();
        $this->patient(['notify_sms_marketing' => true]);

        $this->actingAs($this->admin())->post('/admin/notification-campaigns', [
            'name' => 'Now', 'channel' => 'sms', 'body_ar' => 'مرحبا', 'send_now' => true, 'rules' => [],
        ])->assertRedirect();

        $c = NotificationCampaign::first();
        $this->assertNotNull($c);
        $this->assertSame('sent', $c->status);
    }

    public function test_preview_returns_audience_count(): void
    {
        $this->patient(['gender' => 'female']);
        $this->patient(['gender' => 'female']);

        $this->actingAs($this->admin())->postJson('/admin/notification-campaigns/preview', ['rules' => ['gender' => 'female']])
            ->assertOk()->assertJsonPath('count', 2);
    }

    public function test_dispatch_command_sends_due_scheduled_campaigns(): void
    {
        $this->smsReady();
        $this->patient(['notify_sms_marketing' => true]);

        NotificationCampaign::create([
            'name' => 'Due', 'channel' => 'sms', 'body_ar' => 'مجدولة', 'rules' => [],
            'status' => 'scheduled', 'scheduled_at' => now()->subMinute(),
        ]);
        NotificationCampaign::create([
            'name' => 'Future', 'channel' => 'sms', 'body_ar' => 'لاحقاً', 'rules' => [],
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->artisan('notifications:dispatch-campaigns')->assertExitCode(0);

        $this->assertSame('sent', NotificationCampaign::where('name', 'Due')->first()->status);
        $this->assertSame('scheduled', NotificationCampaign::where('name', 'Future')->first()->status);
    }

    public function test_store_requires_send_permission(): void
    {
        $user = $this->admin(['notifications.view']);
        $this->actingAs($user)->post('/admin/notification-campaigns', [
            'name' => 'X', 'channel' => 'sms', 'body_ar' => 'x',
        ])->assertForbidden();
    }
}

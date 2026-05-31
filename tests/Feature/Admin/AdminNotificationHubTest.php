<?php

namespace Tests\Feature\Admin;

use App\Models\NotificationChannel;
use App\Models\NotificationChannelRoute;
use App\Models\NotificationEvent;
use App\Models\NotificationTemplate;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationHubTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['*']): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name_en' => 'Super', 'display_name_ar' => 'مدير', 'permissions' => $permissions, 'is_system' => true]
        );
        $role->update(['permissions' => $permissions]);

        return User::create([
            'name' => 'Hub Admin', 'email' => 'hub-'.uniqid().'@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_admin_can_view_control_center(): void
    {
        $res = $this->actingAs($this->admin())->get('/admin/notifications-hub');
        $res->assertOk();
        $props = $res->original->getData()['page']['props'];
        $this->assertCount(4, $props['channels']);
        $this->assertNotEmpty($props['events']);
    }

    public function test_admin_can_view_logs(): void
    {
        $this->actingAs($this->admin())->get('/admin/notifications-hub/logs')->assertOk();
    }

    public function test_role_without_permission_is_forbidden(): void
    {
        // 'admin' is in the auth whitelist but lacks notifications.view → 403.
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مشرف', 'permissions' => ['patients.view'], 'is_system' => true]
        );
        $role->update(['permissions' => ['patients.view']]);
        $user = User::create([
            'name' => 'Limited', 'email' => 'limited-'.uniqid().'@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/admin/notifications-hub')->assertForbidden();
    }

    public function test_update_channel_enables_sms_and_stores_secret_encrypted(): void
    {
        $this->actingAs($this->admin())->post('/admin/notifications-hub/channels/sms', [
            'enabled' => true,
            'provider' => 'smsmisr',
            'daily_cap' => 500,
            'sms' => [
                'sms_provider' => 'smsmisr',
                'sms_smsmisr_username' => 'myuser',
                'sms_smsmisr_password' => 'topsecret',
            ],
        ])->assertRedirect();

        $channel = NotificationChannel::for('sms');
        $this->assertTrue($channel->enabled);
        $this->assertSame(500, $channel->daily_cap);
        $this->assertSame('smsmisr', Setting::get('sms_provider'));
        $this->assertSame('topsecret', Setting::get('sms_smsmisr_password'));

        // Raw stored value must be ciphertext, not the plaintext secret.
        $raw = \DB::table('settings')->where('key', 'sms_smsmisr_password')->value('value');
        $this->assertNotSame('topsecret', $raw);
    }

    public function test_blank_secret_does_not_overwrite_existing(): void
    {
        Setting::set('sms_smsmisr_password', 'original');

        $this->actingAs($this->admin())->post('/admin/notifications-hub/channels/sms', [
            'enabled' => true,
            'sms' => ['sms_provider' => 'smsmisr', 'sms_smsmisr_password' => ''],
        ])->assertRedirect();

        $this->assertSame('original', Setting::get('sms_smsmisr_password'));
    }

    public function test_update_channel_stores_email_config_encrypted(): void
    {
        $this->actingAs($this->admin())->post('/admin/notifications-hub/channels/email', [
            'enabled' => true,
            'provider' => 'smtp',
            'config' => ['host' => 'smtp.test', 'username' => 'u', 'password' => 'pw', 'from_address' => 'a@b.c'],
        ])->assertRedirect();

        $channel = NotificationChannel::for('email');
        $this->assertTrue($channel->enabled);
        $this->assertSame('smtp.test', $channel->config['host']);
        $this->assertSame('pw', $channel->config['password']);

        $raw = \DB::table('notification_channels')->where('channel', 'email')->value('config');
        $this->assertStringNotContainsString('smtp.test', (string) $raw); // encrypted at rest
    }

    public function test_update_route_toggles_matrix(): void
    {
        // payment.received is NOT routed to email by the seed → enable it.
        $this->actingAs($this->admin())->post('/admin/notifications-hub/routes', [
            'event_key' => 'payment.received', 'channel' => 'email', 'enabled' => true, 'priority' => 5,
        ])->assertRedirect();

        $route = NotificationChannelRoute::where('event_key', 'payment.received')->where('channel', 'email')->first();
        $this->assertNotNull($route);
        $this->assertTrue($route->enabled);
        $this->assertSame(5, $route->priority);
    }

    public function test_update_event_toggles_active(): void
    {
        $this->actingAs($this->admin())->post('/admin/notifications-hub/events/lead.welcome', ['is_active' => false])->assertRedirect();
        $this->assertFalse((bool) NotificationEvent::where('key', 'lead.welcome')->first()->is_active);
    }

    public function test_store_and_delete_template(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/notifications-hub/templates', [
            'event_key' => 'booking.confirmed', 'channel' => 'sms',
            'body_ar' => 'مرحبا {{name}}', 'body_en' => 'Hi {{name}}', 'is_active' => true,
        ])->assertRedirect();

        $tpl = NotificationTemplate::where('event_key', 'booking.confirmed')->where('channel', 'sms')->first();
        $this->assertNotNull($tpl);

        $this->actingAs($admin)->post("/admin/notifications-hub/templates/{$tpl->id}/delete")->assertRedirect();
        $this->assertNull(NotificationTemplate::find($tpl->id));
    }

    public function test_update_settings_persists_global_cap(): void
    {
        $this->actingAs($this->admin())->post('/admin/notifications-hub/settings', [
            'notifications_global_daily_cap' => 1000,
            'sms_cost_per_segment' => 0.15,
        ])->assertRedirect();

        $this->assertSame('1000', Setting::get('notifications_global_daily_cap'));
        $this->assertSame('0.15', Setting::get('sms_cost_per_segment'));
    }

    public function test_test_endpoint_requires_send_permission(): void
    {
        // has view/update but NOT send
        $user = $this->admin(['notifications.view', 'notifications.update']);
        $this->actingAs($user)->post('/admin/notifications-hub/test', ['channel' => 'in_app', 'to' => 'x'])
            ->assertForbidden();
    }
}

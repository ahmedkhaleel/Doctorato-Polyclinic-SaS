<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $perms = ['*']): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => $perms, 'is_system' => true]);
        $role->update(['permissions' => $perms]);

        return User::create(['name' => 'A', 'email' => 'an-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    private function log(string $channel, string $status, array $extra = []): void
    {
        NotificationLog::create(array_merge([
            'channel' => $channel, 'event_key' => 'booking.confirmed', 'status' => $status,
            'cost' => 0.25, 'error' => $status === 'failed' ? 'timeout' : null,
        ], $extra));
    }

    public function test_analytics_aggregates_by_channel_and_totals(): void
    {
        $this->log('sms', 'sent');
        $this->log('sms', 'delivered');
        $this->log('sms', 'failed');
        $this->log('whatsapp', 'read');
        $this->log('email', 'skipped', ['cost' => 0]);

        $res = $this->actingAs($this->admin())->get('/admin/notifications-hub/analytics');
        $res->assertOk();
        $props = $res->original->getData()['page']['props'];

        $this->assertSame(2, $props['byChannel']['sms']['sent'] + $props['byChannel']['sms']['delivered']);
        $this->assertSame(1, $props['byChannel']['sms']['failed']);
        // sms delivery rate = reached(2) / attempted(3) = 66.7
        $this->assertSame(66.7, $props['byChannel']['sms']['delivery_rate']);
        $this->assertSame(3, $props['totals']['sent']); // sms sent+delivered + whatsapp read
        $this->assertSame(1, $props['totals']['failed']);
        $this->assertSame(1, $props['totals']['skipped']);
    }

    public function test_analytics_lists_failures_and_events(): void
    {
        $this->log('sms', 'failed');
        $this->log('sms', 'failed');

        $props = $this->actingAs($this->admin())->get('/admin/notifications-hub/analytics')
            ->original->getData()['page']['props'];

        $this->assertNotEmpty($props['failures']);
        $this->assertSame('timeout', $props['failures'][0]['error']);
        $this->assertSame(2, (int) $props['failures'][0]['c']);
        $this->assertNotEmpty($props['perEvent']);
    }

    public function test_analytics_requires_permission(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Ad', 'display_name_ar' => 'م', 'permissions' => ['patients.view'], 'is_system' => true]);
        $role->update(['permissions' => ['patients.view']]);
        $user = User::create(['name' => 'L', 'email' => 'l-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->get('/admin/notifications-hub/analytics')->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\NotificationHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthy_when_no_backlog_and_channels_ok(): void
    {
        $report = app(NotificationHealthService::class)->report();
        $this->assertTrue($report['ok']);
        $this->assertSame(0, $report['queue_backlog']);
    }

    public function test_queue_backlog_flags_unhealthy(): void
    {
        $log = NotificationLog::create(['channel' => 'sms', 'event_key' => 'booking.confirmed', 'status' => 'queued']);
        NotificationLog::where('id', $log->id)->update(['created_at' => now()->subHours(2)]);

        $report = app(NotificationHealthService::class)->report();
        $this->assertFalse($report['ok']);
        $this->assertSame(1, $report['queue_backlog']);
    }

    public function test_enabled_unconfigured_channel_flags_unhealthy(): void
    {
        NotificationChannel::where('channel', 'email')->update(['enabled' => true]); // no SMTP config

        $report = app(NotificationHealthService::class)->report();
        $this->assertFalse($report['ok']);
        $this->assertContains('email', $report['channels_unconfigured']);
    }

    public function test_health_endpoint_includes_notifications(): void
    {
        $res = $this->get('/health');
        $res->assertOk(); // notifications check is informational, never 503
        $this->assertArrayHasKey('notifications', $res->json('checks'));
    }

    public function test_diagnostics_page_includes_notifications_card(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => ['*'], 'is_system' => true]);
        $admin = User::create(['name' => 'A', 'email' => 'diag-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $res = $this->actingAs($admin)->get('/admin/diagnostics');
        $res->assertOk();
        $this->assertArrayHasKey('notifications', $res->original->getData()['page']['props']);
    }
}

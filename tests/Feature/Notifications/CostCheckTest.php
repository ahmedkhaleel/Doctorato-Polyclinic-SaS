<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostCheckTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => ['*'], 'is_system' => true]);

        return User::create(['name' => 'A', 'email' => 'cc-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    private function spend(float $cost): void
    {
        NotificationLog::create([
            'channel' => 'sms', 'event_key' => 'booking.confirmed', 'status' => 'sent', 'cost' => $cost,
        ]);
    }

    public function test_alerts_admins_when_cap_exceeded(): void
    {
        $admin = $this->admin();
        Setting::set('notifications_monthly_cost_cap', '10');
        $this->spend(7);
        $this->spend(5); // total 12 >= 10

        $this->artisan('notifications:cost-check')->assertExitCode(0);

        $this->assertTrue(NotificationLog::where('event_key', 'staff.cost_alert')
            ->where('recipient_id', $admin->id)->where('channel', 'in_app')->exists());
    }

    public function test_no_alert_below_cap(): void
    {
        $admin = $this->admin();
        Setting::set('notifications_monthly_cost_cap', '100');
        $this->spend(20);

        $this->artisan('notifications:cost-check')->assertExitCode(0);

        $this->assertFalse(NotificationLog::where('event_key', 'staff.cost_alert')->exists());
    }

    public function test_alert_fires_once_per_month(): void
    {
        $admin = $this->admin();
        Setting::set('notifications_monthly_cost_cap', '10');
        $this->spend(15);

        $this->artisan('notifications:cost-check')->assertExitCode(0);
        $this->artisan('notifications:cost-check')->assertExitCode(0); // second run deduped

        $this->assertSame(1, NotificationLog::where('event_key', 'staff.cost_alert')->where('recipient_id', $admin->id)->count());
    }

    public function test_disabled_when_cap_zero(): void
    {
        $this->admin();
        Setting::set('notifications_monthly_cost_cap', '0');
        $this->spend(999);

        $this->artisan('notifications:cost-check')->assertExitCode(0);
        $this->assertFalse(NotificationLog::where('event_key', 'staff.cost_alert')->exists());
    }
}

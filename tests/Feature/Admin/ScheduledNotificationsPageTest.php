<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\ScheduledNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledNotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $r = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'A', 'display_name_ar' => 'A', 'permissions' => ['*'], 'is_system' => true]);
        $r->update(['permissions' => ['*']]);

        return User::create(['name' => 'A', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    private function pending(): ScheduledNotification
    {
        return ScheduledNotification::create([
            'event_key' => 'booking.reminder', 'channels' => ['sms'], 'reason' => 'reminder',
            'send_after' => now()->addDay(), 'status' => 'pending',
        ]);
    }

    public function test_scheduled_page_loads(): void
    {
        $this->pending();
        $this->actingAs($this->admin())->get('/admin/notifications-hub/scheduled')->assertOk();
    }

    public function test_can_cancel_a_pending_scheduled_notification(): void
    {
        $n = $this->pending();
        $this->actingAs($this->admin())
            ->post("/admin/notifications-hub/scheduled/{$n->id}/cancel")
            ->assertRedirect();

        $this->assertSame('cancelled', $n->fresh()->status);
    }

    public function test_processed_notification_is_not_cancellable(): void
    {
        $n = $this->pending();
        $n->update(['status' => 'processed']);

        $this->actingAs($this->admin())->post("/admin/notifications-hub/scheduled/{$n->id}/cancel")->assertRedirect();
        $this->assertSame('processed', $n->fresh()->status); // unchanged
    }
}

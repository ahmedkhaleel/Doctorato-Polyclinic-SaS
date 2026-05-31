<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\NotificationFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InAppFeedTest extends TestCase
{
    use RefreshDatabase;

    private function patientWithUser(): Patient
    {
        $role = Role::firstOrCreate(['name' => 'patient'],
            ['display_name_en' => 'P', 'display_name_ar' => 'م', 'permissions' => [], 'is_system' => true]);
        $user = User::create([
            'name' => 'Feed', 'email' => 'feed-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        $p = Patient::create(['full_name' => 'Feed Patient', 'phone' => '01012345678']);
        $p->forceFill(['user_id' => $user->id, 'is_active' => true])->save();

        return $p;
    }

    private function inAppLog(Patient $p, array $attrs = []): NotificationLog
    {
        return NotificationLog::create(array_merge([
            'recipient_type' => $p->getMorphClass(), 'recipient_id' => $p->id,
            'channel' => 'in_app', 'event_key' => 'booking.confirmed', 'status' => 'sent',
            'meta' => ['body' => 'تم تأكيد حجزك'],
        ], $attrs));
    }

    // ── service unit-level ──────────────────────────────
    public function test_feed_returns_only_recipient_in_app_logs(): void
    {
        $p = $this->patientWithUser();
        $other = $this->patientWithUser();
        $this->inAppLog($p);
        $this->inAppLog($other); // belongs to someone else
        // a non-in_app log for p must NOT appear
        $this->inAppLog($p, ['channel' => 'sms']);

        $feed = app(NotificationFeedService::class)->feed($p);
        $this->assertCount(1, $feed);
        $this->assertSame('تم تأكيد حجزك', $feed->first()['body']);
    }

    public function test_unread_count_and_mark_read(): void
    {
        $p = $this->patientWithUser();
        $a = $this->inAppLog($p);
        $this->inAppLog($p);
        $svc = app(NotificationFeedService::class);

        $this->assertSame(2, $svc->unreadCount($p));
        $this->assertTrue($svc->markRead($p, $a->id));
        $this->assertSame(1, $svc->unreadCount($p));
        $a->refresh();
        $this->assertNotNull($a->read_at);
        $this->assertSame('read', $a->status);
    }

    public function test_mark_read_rejects_other_patients_log(): void
    {
        $p = $this->patientWithUser();
        $other = $this->patientWithUser();
        $log = $this->inAppLog($other);

        $this->assertFalse(app(NotificationFeedService::class)->markRead($p, $log->id));
        $this->assertNull($log->refresh()->read_at);
    }

    // ── HTTP endpoints ──────────────────────────────────
    public function test_bell_endpoint_returns_unread_and_items(): void
    {
        $p = $this->patientWithUser();
        $this->inAppLog($p);

        $res = $this->actingAs($p->user)->getJson('/ar/patient/notifications/bell');
        $res->assertOk()->assertJsonPath('unread', 1);
        $this->assertCount(1, $res->json('items'));
    }

    public function test_feed_page_renders(): void
    {
        $p = $this->patientWithUser();
        $this->inAppLog($p);

        $res = $this->actingAs($p->user)->get('/ar/patient/notifications');
        $res->assertOk();
        $props = $res->original->getData()['page']['props'];
        $this->assertSame(1, $props['unreadCount']);
    }

    public function test_mark_all_read_endpoint(): void
    {
        $p = $this->patientWithUser();
        $this->inAppLog($p);
        $this->inAppLog($p);

        $this->actingAs($p->user)->post('/ar/patient/notifications/read-all')->assertRedirect();
        $this->assertSame(0, app(NotificationFeedService::class)->unreadCount($p));
    }

    public function test_bell_requires_auth(): void
    {
        // patient.auth redirects guests to login (302), it does not 401.
        $this->get('/ar/patient/notifications/bell')->assertRedirect();
    }
}

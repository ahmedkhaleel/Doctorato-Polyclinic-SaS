<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => ['*'], 'is_system' => true]);

        return User::create(['name' => 'A', 'email' => 'ib-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    private function inbound(string $from, string $body): NotificationLog
    {
        return NotificationLog::create([
            'to' => $from, 'channel' => 'whatsapp', 'event_key' => 'inbound.whatsapp',
            'status' => 'delivered', 'meta' => ['direction' => 'inbound', 'body' => $body], 'delivered_at' => now(),
        ]);
    }

    private function outbound(string $to, string $body): NotificationLog
    {
        return NotificationLog::create([
            'to' => $to, 'channel' => 'whatsapp', 'event_key' => 'booking.confirmed',
            'status' => 'sent', 'meta' => ['body' => $body],
        ]);
    }

    public function test_inbox_groups_conversations_with_unread_count(): void
    {
        $this->inbound('201012345678', 'مرحبا');
        $this->inbound('201012345678', 'أريد موعد'); // 2 unread from same contact
        $this->outbound('201099998888', 'تأكيد');     // separate contact, no unread

        $props = $this->actingAs($this->admin())->get('/admin/inbox')
            ->original->getData()['page']['props'];

        $convos = collect($props['conversations']['data']);
        $this->assertCount(2, $convos);
        $first = $convos->firstWhere('contact', '201012345678');
        $this->assertSame(2, $first['unread']);
        $this->assertSame(2, $first['messages']);
    }

    public function test_opening_conversation_marks_inbound_read_and_returns_thread(): void
    {
        $a = $this->inbound('201012345678', 'سؤال');
        $this->outbound('201012345678', 'جواب');

        $props = $this->actingAs($this->admin())->get('/admin/inbox?contact=201012345678')
            ->original->getData()['page']['props'];

        $this->assertNotNull($props['active']);
        $this->assertSame('201012345678', $props['active']['contact']);
        $this->assertCount(2, $props['active']['messages']);
        $this->assertSame('inbound', $props['active']['messages'][0]['direction']);

        $a->refresh();
        $this->assertNotNull($a->read_at); // opening marked it read
    }

    public function test_conversation_links_to_matching_patient(): void
    {
        $p = Patient::create(['full_name' => 'Inbox Patient', 'phone' => '01012345678']);
        $this->inbound('201012345678', 'مرحبا');

        $props = $this->actingAs($this->admin())->get('/admin/inbox?contact=201012345678')
            ->original->getData()['page']['props'];

        $this->assertSame($p->id, $props['active']['patient']['id']);
    }

    public function test_reply_sends_outbound_message(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
        $this->inbound('201012345678', 'مرحبا');

        $this->actingAs($this->admin())->post('/admin/inbox/reply', [
            'contact' => '201012345678', 'body' => 'أهلاً بك', 'channel' => 'sms',
        ])->assertRedirect();

        $sent = NotificationLog::where('to', '201012345678')->where('event_key', 'manual.message')
            ->where('status', 'sent')->first();
        $this->assertNotNull($sent);
        $this->assertSame('أهلاً بك', $sent->meta['body']);
    }

    public function test_reply_requires_send_permission(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Ad', 'display_name_ar' => 'م', 'permissions' => ['notifications.view'], 'is_system' => true]);
        $role->update(['permissions' => ['notifications.view']]);
        $user = User::create(['name' => 'L', 'email' => 'l-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->post('/admin/inbox/reply', ['contact' => '201012345678', 'body' => 'x'])
            ->assertForbidden();
    }
}

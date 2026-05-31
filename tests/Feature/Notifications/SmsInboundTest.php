<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationConsent;
use App\Models\NotificationLog;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsInboundTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_sms_recorded_as_conversation_entry(): void
    {
        $this->post('/webhooks/sms/inbound', [
            'From' => '+201012345678', 'Body' => 'مرحبا أريد موعد', 'MessageSid' => 'IN1',
        ])->assertOk();

        $log = NotificationLog::where('event_key', 'inbound.sms')->first();
        $this->assertNotNull($log);
        $this->assertSame('+201012345678', $log->to);
        $this->assertSame('inbound', $log->meta['direction']);
        $this->assertSame('مرحبا أريد موعد', $log->meta['body']);
    }

    public function test_inbound_sms_stop_opts_patient_out(): void
    {
        $p = new Patient(['full_name' => 'Stop', 'phone' => '01012345678']);
        $p->file_number = 'P-STOP-'.uniqid();
        $p->is_active = true;
        $p->forceFill(['notify_sms_marketing' => true])->save();

        $this->post('/webhooks/sms/inbound', ['From' => '201012345678', 'Body' => 'STOP'])->assertOk();

        $p->refresh();
        $this->assertFalse((bool) $p->notify_sms_marketing);
        $this->assertTrue(NotificationConsent::where('recipient_id', $p->id)->where('source', 'stop_keyword')->exists());
    }

    public function test_inbound_sms_shows_in_admin_inbox(): void
    {
        $this->post('/webhooks/sms/inbound', ['From' => '201099998888', 'Body' => 'استفسار', 'MessageSid' => 'IN2'])->assertOk();

        $role = \App\Models\Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => ['*'], 'is_system' => true]);
        $admin = \App\Models\User::create(['name' => 'A', 'email' => 'in-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $props = $this->actingAs($admin)->get('/admin/inbox?contact=201099998888')
            ->original->getData()['page']['props'];

        $this->assertNotNull($props['active']);
        $this->assertSame('inbound', $props['active']['messages'][0]['direction']);
    }

    public function test_inbound_endpoint_is_csrf_exempt(): void
    {
        $this->post('/webhooks/sms/inbound', ['From' => '201000', 'Body' => 'x'])->assertOk();
    }
}

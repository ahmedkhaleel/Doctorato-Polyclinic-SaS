<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Services\Notifications\Notifier;
use App\Services\Notifications\WhatsAppTemplateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTemplateRegistryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $perms = ['*']): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => $perms, 'is_system' => true]);
        $role->update(['permissions' => $perms]);

        return User::create(['name' => 'A', 'email' => 'wt-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    private function enableWhatsApp(): void
    {
        $c = NotificationChannel::for('whatsapp');
        $c->enabled = true;
        $c->provider = 'cloud_api';
        $c->config = ['phone_number_id' => '1', 'access_token' => 't'];
        $c->save();
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'WT', 'phone' => '01012345678']);
        $p->file_number = 'P-WT-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    public function test_resolver_builds_body_components_from_data(): void
    {
        WhatsappTemplate::create([
            'name' => 'appt', 'language' => 'ar', 'event_key' => 'booking.confirmed',
            'variables' => ['name', 'date'], 'is_active' => true,
        ]);

        $meta = app(WhatsAppTemplateResolver::class)->metaFor('booking.confirmed', ['name' => 'أحمد', 'date' => '2026-06-01']);

        $this->assertSame('appt', $meta['template_name']);
        $this->assertSame('ar', $meta['template_lang']);
        $this->assertSame('أحمد', $meta['template_components'][0]['parameters'][0]['text']);
        $this->assertSame('2026-06-01', $meta['template_components'][0]['parameters'][1]['text']);
    }

    public function test_resolver_returns_null_without_active_template(): void
    {
        $this->assertNull(app(WhatsAppTemplateResolver::class)->metaFor('booking.confirmed', []));
    }

    public function test_whatsapp_send_uses_registered_template_payload(): void
    {
        $this->enableWhatsApp();
        WhatsappTemplate::create([
            'name' => 'booking_confirm', 'language' => 'ar', 'event_key' => 'booking.confirmed',
            'variables' => ['name'], 'is_active' => true,
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.T']]], 200)]);

        Notifier::eventNow('booking.confirmed', $this->patient(), ['name' => 'أحمد', 'body' => 'fallback']);

        Http::assertSent(fn ($r) => $r['type'] === 'template'
            && $r['template']['name'] === 'booking_confirm'
            && $r['template']['components'][0]['parameters'][0]['text'] === 'أحمد');
    }

    public function test_admin_can_crud_templates(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/notifications-hub/whatsapp-templates', [
            'name' => 'welcome_t', 'language' => 'ar', 'event_key' => 'lead.welcome',
            'variables' => ['name'], 'is_active' => true,
        ])->assertRedirect();

        $tpl = WhatsappTemplate::first();
        $this->assertNotNull($tpl);
        $this->assertSame('welcome_t', $tpl->name);

        $this->actingAs($admin)->post("/admin/notifications-hub/whatsapp-templates/{$tpl->id}/delete")->assertRedirect();
        $this->assertNull(WhatsappTemplate::find($tpl->id));
    }

    public function test_crud_requires_permission(): void
    {
        $user = $this->admin(['notifications.view']);
        $this->actingAs($user)->post('/admin/notifications-hub/whatsapp-templates', [
            'name' => 'x', 'language' => 'ar',
        ])->assertForbidden();
    }
}

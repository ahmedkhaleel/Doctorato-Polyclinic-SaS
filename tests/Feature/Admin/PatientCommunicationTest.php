<?php

namespace Tests\Feature\Admin;

use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCommunicationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['*']): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => $permissions, 'is_system' => true]);
        $role->update(['permissions' => $permissions]);

        return User::create(['name' => 'A', 'email' => 'pc-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Comm Patient', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-COMM-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs);
        $p->save();

        return $p;
    }

    public function test_communications_page_shows_patient_logs(): void
    {
        $patient = $this->patient();
        NotificationLog::create([
            'recipient_type' => $patient->getMorphClass(), 'recipient_id' => $patient->id,
            'channel' => 'sms', 'event_key' => 'booking.confirmed', 'status' => 'sent', 'to' => $patient->phone,
        ]);

        $res = $this->actingAs($this->admin())->get("/admin/patients/{$patient->id}/communications");
        $res->assertOk();
        $props = $res->original->getData()['page']['props'];
        $this->assertCount(1, $props['logs']);
        $this->assertArrayHasKey('notify_whatsapp_marketing', $props['preferences']);
    }

    public function test_manual_send_in_app_creates_log(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->admin())->post("/admin/patients/{$patient->id}/communications/send", [
            'channel' => 'in_app', 'body' => 'رسالة يدوية للمريض',
        ])->assertRedirect();

        $log = NotificationLog::where('recipient_id', $patient->id)->where('channel', 'in_app')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame('رسالة يدوية للمريض', $log->meta['body']);
    }

    public function test_manual_send_requires_send_permission(): void
    {
        $patient = $this->patient();
        // admin role with patient perms but NOT notifications.send
        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Ad', 'display_name_ar' => 'م', 'permissions' => ['patients.view', 'patients.update'], 'is_system' => true]);
        $role->update(['permissions' => ['patients.view', 'patients.update']]);
        $user = User::create(['name' => 'L', 'email' => 'l-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->post("/admin/patients/{$patient->id}/communications/send", [
            'channel' => 'in_app', 'body' => 'x',
        ])->assertForbidden();
    }

    public function test_update_preferences_persists_whatsapp_optout(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->admin())->post("/admin/patients/{$patient->id}/communications/preferences", [
            'notify_whatsapp_marketing' => true,
            'notify_sms_reminders' => false,
            'preferred_language' => 'en',
        ])->assertRedirect();

        $patient->refresh();
        $this->assertTrue((bool) $patient->notify_whatsapp_marketing);
        $this->assertFalse((bool) $patient->notify_sms_reminders);
        $this->assertSame('en', $patient->preferred_language);
    }
}

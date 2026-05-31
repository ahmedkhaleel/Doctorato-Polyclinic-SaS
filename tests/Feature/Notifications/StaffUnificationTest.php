<?php

namespace Tests\Feature\Notifications;

use App\Models\ContactMessage;
use App\Models\NotificationLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\StaffNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffUnificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $perms = ['*']): User
    {
        $r = Role::firstOrCreate(['name' => $role],
            ['display_name_en' => $role, 'display_name_ar' => $role, 'permissions' => $perms, 'is_system' => true]);
        $r->update(['permissions' => $perms]);

        return User::create(['name' => $role, 'email' => $role.'-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    public function test_new_contact_message_alerts_front_desk(): void
    {
        $secretary = $this->user('secretary');
        $admin = $this->user('super_admin');

        ContactMessage::create([
            'name' => 'زائر', 'email' => 'v@x.com', 'phone' => '0100',
            'subject' => 'استفسار', 'message' => 'أريد معرفة المواعيد',
        ]);

        foreach ([$secretary, $admin] as $u) {
            $this->assertTrue(NotificationLog::where('event_key', 'staff.new_message')
                ->where('channel', 'in_app')->where('recipient_id', $u->id)->exists());
        }
    }

    public function test_doctor_feed_endpoint_reachable(): void
    {
        $doctor = $this->user('doctor');
        // DoctorAuth requires a linked active Doctor profile.
        \App\Models\Doctor::create(['name_ar' => 'طبيب', 'name_en' => 'Doc', 'status' => 'active'])
            ->forceFill(['user_id' => $doctor->id])->save();

        StaffNotifier::toUser($doctor, 'staff.booking_new', ['body' => 'حجز']);

        $this->actingAs($doctor)->getJson('/doctor/my-notifications/bell')
            ->assertOk()->assertJsonPath('unread', 1);
    }

    public function test_secretary_feed_endpoint_reachable(): void
    {
        $secretary = $this->user('secretary');
        StaffNotifier::toUser($secretary, 'staff.new_message', ['body' => 'رسالة']);

        $this->actingAs($secretary)->getJson('/secretary/my-notifications/bell')
            ->assertOk()->assertJsonPath('unread', 1);
    }

    public function test_secretary_feed_page_uses_secretary_panel(): void
    {
        $secretary = $this->user('secretary');
        $res = $this->actingAs($secretary)->get('/secretary/my-notifications');
        $res->assertOk();
        $this->assertSame('secretary', $res->original->getData()['page']['props']['panel']);
    }
}

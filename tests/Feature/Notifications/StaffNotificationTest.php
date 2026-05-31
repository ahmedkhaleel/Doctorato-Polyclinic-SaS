<?php

namespace Tests\Feature\Notifications;

use App\Events\BookingCreated;
use App\Listeners\NotifyStaffOnBooking;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\StaffNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $roleName, array $perms = ['*']): User
    {
        $role = Role::firstOrCreate(['name' => $roleName],
            ['display_name_en' => $roleName, 'display_name_ar' => $roleName, 'permissions' => $perms, 'is_system' => true]);
        $role->update(['permissions' => $perms]);

        return User::create([
            'name' => $roleName, 'email' => $roleName.'-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_new_booking_notifies_front_desk_staff(): void
    {
        $secretary = $this->user('secretary');
        $admin = $this->user('super_admin');

        $patient = Patient::create(['full_name' => 'Booker', 'phone' => '01012345678']);
        $booking = Booking::create([
            'patient_id' => $patient->id, 'booking_number' => 'BK-'.uniqid(),
            'module' => 'dental', 'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00', 'phone' => $patient->phone, 'full_name' => 'Booker',
        ]);

        (new NotifyStaffOnBooking)->handle(new BookingCreated($booking));

        foreach ([$secretary, $admin] as $u) {
            $this->assertTrue(
                NotificationLog::where('event_key', 'staff.booking_new')
                    ->where('channel', 'in_app')
                    ->where('recipient_type', $u->getMorphClass())
                    ->where('recipient_id', $u->id)
                    ->exists(),
                "expected staff alert for {$u->name}"
            );
        }
    }

    public function test_staff_notifier_to_roles_targets_only_matching_active_users(): void
    {
        $secretary = $this->user('secretary');
        $inactive = $this->user('secretary');
        $inactive->update(['is_active' => false]);
        $doctor = $this->user('doctor');

        $count = StaffNotifier::toRoles(['secretary'], 'staff.low_stock', ['body' => 'نفد المخزون']);

        $this->assertSame(1, $count); // only the active secretary
        $this->assertTrue(NotificationLog::where('recipient_id', $secretary->id)->where('event_key', 'staff.low_stock')->exists());
        $this->assertFalse(NotificationLog::where('recipient_id', $doctor->id)->where('event_key', 'staff.low_stock')->exists());
    }

    public function test_admin_staff_bell_endpoint(): void
    {
        $admin = $this->user('super_admin');
        StaffNotifier::toUser($admin, 'staff.booking_new', ['body' => 'حجز']);

        $res = $this->actingAs($admin)->getJson('/admin/my-notifications/bell');
        $res->assertOk()->assertJsonPath('unread', 1);
    }

    public function test_admin_staff_feed_page_renders(): void
    {
        $admin = $this->user('super_admin');
        StaffNotifier::toUser($admin, 'staff.booking_new', ['body' => 'حجز']);

        $res = $this->actingAs($admin)->get('/admin/my-notifications');
        $res->assertOk();
        $props = $res->original->getData()['page']['props'];
        $this->assertSame('admin', $props['panel']);
        $this->assertSame(1, $props['unreadCount']);
    }

    public function test_staff_feed_is_isolated_per_user(): void
    {
        $a = $this->user('super_admin');
        $b = $this->user('super_admin');
        StaffNotifier::toUser($a, 'staff.booking_new', ['body' => 'A only']);

        $this->actingAs($b)->getJson('/admin/my-notifications/bell')->assertJsonPath('unread', 0);
        $this->actingAs($a)->getJson('/admin/my-notifications/bell')->assertJsonPath('unread', 1);
    }
}

<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoctorNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Doctor', 'email' => 'doc-notif@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        Doctor::create([
            'name_ar' => 'دكتور', 'name_en' => 'Test Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);
    }

    private function createNotification(array $data = [], bool $read = false): string
    {
        $id = Str::uuid()->toString();
        DatabaseNotification::create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->doctorUser->id,
            'data' => array_merge(['type' => 'new_booking', 'patient_name' => 'Ahmed', 'service_name' => 'Laser'], $data),
            'read_at' => $read ? now() : null,
        ]);
        return $id;
    }

    public function test_notification_index_returns_json(): void
    {
        $this->createNotification();

        $this->actingAs($this->doctorUser)
            ->getJson('/doctor/notifications')
            ->assertOk()
            ->assertJsonStructure(['items', 'unread_count']);
    }

    public function test_notification_index_returns_only_unread(): void
    {
        $this->createNotification(read: false);
        $this->createNotification(read: true);

        $response = $this->actingAs($this->doctorUser)
            ->getJson('/doctor/notifications')
            ->assertOk();

        $this->assertEquals(1, $response->json('unread_count'));
    }

    public function test_notification_history_page_loads(): void
    {
        $this->createNotification();

        $this->actingAs($this->doctorUser)
            ->get('/doctor/notifications/history')
            ->assertOk();
    }

    public function test_notification_history_shows_all(): void
    {
        $this->createNotification(read: false);
        $this->createNotification(read: true);

        $response = $this->actingAs($this->doctorUser)
            ->get('/doctor/notifications/history')
            ->assertOk();

        $page = $response->viewData('page');
        $this->assertCount(2, $page['props']['notifications']['data']);
    }

    public function test_notification_history_filter_unread(): void
    {
        $this->createNotification(read: false);
        $this->createNotification(read: true);

        $response = $this->actingAs($this->doctorUser)
            ->get('/doctor/notifications/history?filter=unread')
            ->assertOk();

        $page = $response->viewData('page');
        $this->assertCount(1, $page['props']['notifications']['data']);
    }

    public function test_mark_all_read(): void
    {
        $this->createNotification();
        $this->createNotification();

        $this->actingAs($this->doctorUser)
            ->postJson('/doctor/notifications/mark-all-read')
            ->assertOk();

        $this->assertEquals(0, $this->doctorUser->unreadNotifications()->count());
    }

    public function test_mark_single_read(): void
    {
        $id = $this->createNotification();

        $this->actingAs($this->doctorUser)
            ->postJson("/doctor/notifications/{$id}/read")
            ->assertOk();

        $this->assertNotNull(
            DatabaseNotification::find($id)->read_at
        );
    }

    public function test_notification_types_have_correct_urls(): void
    {
        $this->createNotification(['type' => 'new_visit', 'visit_id' => 42, 'patient_name' => 'Sara']);

        $response = $this->actingAs($this->doctorUser)
            ->getJson('/doctor/notifications')
            ->assertOk();

        $item = $response->json('items.0');
        $this->assertEquals('/doctor/visits/42', $item['url']);
        $this->assertEquals('Sara', $item['title']);
    }
}

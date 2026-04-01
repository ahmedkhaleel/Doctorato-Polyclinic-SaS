<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactMessageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-msg@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_messages_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/contact-messages')->assertOk();
    }

    public function test_can_view_single_message(): void
    {
        $msg = ContactMessage::create([
            'name' => 'John Doe', 'email' => 'john@test.com',
            'subject' => 'Inquiry', 'message' => 'Hello, I have a question.',
        ]);

        $this->actingAs($this->admin)->get("/admin/contact-messages/{$msg->id}")->assertOk();
    }

    public function test_viewing_message_marks_as_read(): void
    {
        $msg = ContactMessage::create([
            'name' => 'Jane', 'email' => 'jane@test.com',
            'subject' => 'Help', 'message' => 'Need help.',
            'is_read' => false,
        ]);

        $this->actingAs($this->admin)->get("/admin/contact-messages/{$msg->id}");

        $this->assertDatabaseHas('contact_messages', ['id' => $msg->id, 'is_read' => true]);
    }

    public function test_can_delete_message(): void
    {
        $msg = ContactMessage::create([
            'name' => 'Del', 'email' => 'del@test.com',
            'subject' => 'Delete', 'message' => 'Delete me.',
        ]);

        $this->actingAs($this->admin)->delete("/admin/contact-messages/{$msg->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('contact_messages', ['id' => $msg->id]);
    }
}

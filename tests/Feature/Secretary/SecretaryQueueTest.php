<?php

namespace Tests\Feature\Secretary;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true]
        );

        $this->secretary = User::create([
            'name' => 'Queue Secretary', 'email' => 'sec-queue@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_queue(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/queue')->assertOk();
    }
}

<?php

namespace Tests\Feature\Secretary;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryPatientSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaryUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتيرة', 'permissions' => [], 'is_system' => true]
        );

        $this->secretaryUser = User::create([
            'name' => 'Secretary', 'email' => 'sec-search@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        // Create test patients
        foreach ([
            ['full_name' => 'Ahmed Hassan', 'phone' => '01001001001', 'file_number' => 'P-S-001'],
            ['full_name' => 'Sara Mohamed', 'phone' => '01002002002', 'file_number' => 'P-S-002'],
            ['full_name' => 'Inactive User', 'phone' => '01003003003', 'file_number' => 'P-S-003'],
        ] as $i => $data) {
            $p = new Patient($data);
            $p->file_number = $data['file_number'];
            $p->is_active = $i < 2; // third one is inactive
            $p->save();
        }
    }

    public function test_search_by_name(): void
    {
        $this->actingAs($this->secretaryUser)
            ->getJson('/secretary/api/patients?q=Ahmed Hassan')
            ->assertOk()
            ->assertJsonFragment(['full_name' => 'Ahmed Hassan']);
    }

    public function test_search_by_phone(): void
    {
        $this->actingAs($this->secretaryUser)
            ->getJson('/secretary/api/patients?q=01002')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['full_name' => 'Sara Mohamed']);
    }

    public function test_search_by_file_number(): void
    {
        $this->actingAs($this->secretaryUser)
            ->getJson('/secretary/api/patients?q=P-S-001')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_search_excludes_inactive_patients(): void
    {
        $this->actingAs($this->secretaryUser)
            ->getJson('/secretary/api/patients?q=Inactive')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_search_requires_min_chars(): void
    {
        $this->actingAs($this->secretaryUser)
            ->getJson('/secretary/api/patients?q=A')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_search_limits_results(): void
    {
        // Create 25 patients with similar names
        for ($i = 0; $i < 25; $i++) {
            $p = new Patient(['full_name' => "Bulk Patient {$i}", 'phone' => '0500' . str_pad($i, 7, '0', STR_PAD_LEFT)]);
            $p->file_number = 'P-BULK-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $p->is_active = true;
            $p->save();
        }

        $response = $this->actingAs($this->secretaryUser)
            ->getJson('/secretary/api/patients?q=Bulk')
            ->assertOk();

        $this->assertLessThanOrEqual(20, count($response->json()));
    }
}

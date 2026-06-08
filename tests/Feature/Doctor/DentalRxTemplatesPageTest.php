<?php

namespace Tests\Feature\Doctor;

use App\Models\DentalPrescriptionTemplate;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The doctor "Rx Templates" sidebar link must render an Inertia PAGE (it used to
 * point at a JSON endpoint → Inertia "Server error"). The JSON list lives at a
 * separate /list path.
 */
class DentalRxTemplatesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'D', 'email' => 'rx-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'dental']);
        ModuleManager::enable('dental');
        ModuleManager::flushStaticCache();

        $t = DentalPrescriptionTemplate::create(['name_ar' => 'قالب', 'name_en' => 'Post-extraction', 'treatment_type' => 'extraction', 'is_active' => true, 'sort_order' => 1]);
        $t->items()->create(['medication_name' => 'Amoxicillin', 'dosage' => '500mg', 'frequency' => 'TID', 'duration' => '5d']);
    }

    public function test_rx_templates_route_renders_an_inertia_page(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/dental/prescription-templates')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Doctor/Dental/PrescriptionTemplates/Index')
                ->has('templates', 1)
                ->where('templates.0.name_en', 'Post-extraction')
                ->has('templates.0.items', 1)
            );
    }

    public function test_json_list_endpoint_still_serves_data(): void
    {
        $this->actingAs($this->doctorUser)
            ->getJson('/doctor/dental/prescription-templates/list')
            ->assertOk()
            ->assertJsonFragment(['name_en' => 'Post-extraction']);
    }
}

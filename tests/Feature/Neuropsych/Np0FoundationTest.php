<?php

namespace Tests\Feature\Neuropsych;

use App\Models\NeuropsychProfile;
use App\Models\Patient;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP0 — foundation: the psychiatry & neurology modules are registered as
 * medical specialties (off by default, toggleable), the shared patient profile
 * table works, and the permission families are in the registry.
 */
class Np0FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_modules_are_registered_as_medical_specialties(): void
    {
        $this->assertContains('psychiatry', ModuleManager::MEDICAL_MODULES);
        $this->assertContains('neurology', ModuleManager::MEDICAL_MODULES);
        $this->assertArrayHasKey('psychiatry', ModuleManager::MODULES);
        $this->assertArrayHasKey('neurology', ModuleManager::MODULES);
    }

    public function test_modules_are_disabled_by_default_and_can_be_toggled(): void
    {
        ModuleManager::flushStaticCache();
        $this->assertFalse(ModuleManager::isEnabled('psychiatry'));
        $this->assertFalse(ModuleManager::isEnabled('neurology'));

        $this->assertTrue(ModuleManager::enable('psychiatry'));
        ModuleManager::flushStaticCache();
        $this->assertTrue(ModuleManager::isEnabled('psychiatry'));
        // neurology stays independent.
        $this->assertFalse(ModuleManager::isEnabled('neurology'));
    }

    public function test_shared_profile_is_one_per_patient(): void
    {
        $patient = new Patient(['full_name' => 'NP Patient', 'phone' => '0500001234', 'gender' => 'male']);
        $patient->file_number = 'PAT-NP-001';
        $patient->is_active = true;
        $patient->save();

        $profile = NeuropsychProfile::create([
            'patient_id' => $patient->id,
            'chief_complaint' => 'Low mood',
            'substance_history' => ['tobacco' => true],
            'risk_factors' => ['family_history_suicide'],
        ]);

        $this->assertDatabaseHas('neuropsych_profiles', ['patient_id' => $patient->id]);
        $this->assertSame(['family_history_suicide'], $profile->fresh()->risk_factors);
        $this->assertTrue($profile->fresh()->substance_history['tobacco']);
    }

    public function test_permission_families_are_registered(): void
    {
        $modules = config('permissions.modules', []);
        $this->assertArrayHasKey('psychiatry', $modules);
        $this->assertArrayHasKey('neurology', $modules);
        $this->assertContains('view_sensitive', $modules['psychiatry']['actions']);
        $this->assertContains('view_sensitive', $modules['neurology']['actions']);
    }
}

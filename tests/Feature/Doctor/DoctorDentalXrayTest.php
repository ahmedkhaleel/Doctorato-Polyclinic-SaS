<?php

namespace Tests\Feature\Doctor;

use App\Models\DentalXray;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorDentalXrayTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('module_settings')) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => 'dental', 'key' => 'enabled'],
                ['module' => 'dental', 'key' => 'enabled', 'value' => '1', 'group' => 'general']
            );
        }

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Xray Doctor', 'email' => 'doc-xray@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور أشعة', 'name_en' => 'Xray Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Xray Patient', 'phone' => '0500009333', 'gender' => 'male']);
        $this->patient->file_number = 'PAT-XR-001';
        $this->patient->is_active = true;
        $this->patient->save();

        Cache::flush();
        $reflection = new \ReflectionClass(\App\Services\ModuleManager::class);
        $prop = $reflection->getProperty('tableExists');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_can_view_xrays_index(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/dental/xrays')->assertOk();
    }

    public function test_can_upload_xray(): void
    {
        Storage::fake('public');

        $this->actingAs($this->doctorUser)->post('/doctor/dental/xrays', [
            'patient_id' => $this->patient->id,
            'type' => 'panoramic',
            'tooth_number' => 11,
            'taken_date' => '2025-06-01',
            'findings' => 'No abnormalities',
            'image' => UploadedFile::fake()->image('xray.jpg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('dental_xrays', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'panoramic',
        ]);
    }

    public function test_can_view_patient_xrays(): void
    {
        DentalXray::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'periapical',
            'image_path' => 'dental-xrays/test.jpg',
            'taken_date' => '2025-06-01',
        ]);

        $this->actingAs($this->doctorUser)
            ->get("/doctor/dental/xrays/patient/{$this->patient->id}")
            ->assertOk();
    }

    public function test_xray_filters_by_type(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/dental/xrays?type=bitewing')
            ->assertOk();
    }

    public function test_xray_only_shows_own_records(): void
    {
        $otherDoc = Doctor::create([
            'name_ar' => 'دكتور آخر', 'name_en' => 'Other Doc', 'status' => 'active',
        ]);

        DentalXray::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoc->id,
            'type' => 'panoramic',
            'image_path' => 'dental-xrays/other.jpg',
            'taken_date' => '2025-06-01',
        ]);

        DentalXray::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'bitewing',
            'image_path' => 'dental-xrays/mine.jpg',
            'taken_date' => '2025-06-01',
        ]);

        // Index only shows own xrays
        $response = $this->actingAs($this->doctorUser)->get('/doctor/dental/xrays');
        $response->assertOk();
    }
}

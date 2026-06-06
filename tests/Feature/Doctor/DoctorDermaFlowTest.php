<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Doctor dermatology/cosmetic workspace (P1): the treating doctor can open
 * the derma panel, log a session, and have it billed into a derma-tagged
 * invoice — closing the gap where derma had no doctor-side workspace.
 */
class DoctorDermaFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::enable('derma');

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );
        $this->doctorUser = User::create([
            'name' => 'Derma Doc', 'email' => 'derma-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        $this->doctor = Doctor::create([
            'name_ar' => 'د. جلدية', 'name_en' => 'Derma Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'derma',
        ]);
    }

    #[Test]
    public function dashboard_and_patients_pages_render(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/derma')->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/derma/patients')->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/derma/treatment-plans')->assertOk();
    }

    #[Test]
    public function logging_a_session_with_cost_bills_a_derma_tagged_invoice(): void
    {
        $patient = Patient::create(['full_name' => 'Derma Patient', 'phone' => '0100']);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/derma/patients/{$patient->id}/sessions", [
                'session_type' => 'laser', 'area_treated' => 'face', 'cost' => 400,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('derma_sessions', [
            'patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'session_type' => 'laser',
        ]);
        $invoice = Invoice::where('module', 'derma')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(400, (float) $invoice->total);
    }

    #[Test]
    public function zero_cost_session_is_logged_but_not_billed(): void
    {
        $patient = Patient::create(['full_name' => 'NoBill', 'phone' => '0101']);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/derma/patients/{$patient->id}/sessions", ['session_type' => 'peel'])
            ->assertRedirect();

        $this->assertDatabaseHas('derma_sessions', ['patient_id' => $patient->id, 'session_type' => 'peel']);
        $this->assertSame(0, Invoice::where('module', 'derma')->count());
    }

    #[Test]
    public function logging_a_cosmetic_session_with_cost_bills_a_derma_invoice(): void
    {
        $patient = Patient::create(['full_name' => 'Cosmo', 'phone' => '0103']);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/derma/patients/{$patient->id}/cosmetic-sessions", [
                'area_treated' => 'forehead', 'cost' => 600,
            ])->assertRedirect();

        $this->assertDatabaseHas('cosmetic_sessions', ['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id]);
        $this->assertEquals(600, (float) Invoice::where('module', 'derma')->first()?->total);
    }

    #[Test]
    public function uploading_a_before_photo_stores_a_derma_photo(): void
    {
        // S1: PHI photos now land on the private `local` disk, served via signed URLs.
        Storage::fake('local');
        $patient = Patient::create(['full_name' => 'Pic', 'phone' => '0104']);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/derma/patients/{$patient->id}/photos", [
                'category' => 'before',
                'body_area' => 'face',
                'image' => UploadedFile::fake()->image('before.jpg', 600, 600),
            ])->assertRedirect();

        $photo = \App\Models\DermaPhoto::where('patient_id', $patient->id)->first();
        $this->assertNotNull($photo);
        $this->assertSame('before', $photo->category);
        Storage::disk('local')->assertExists($photo->image_path);
        // And the model exposes a signed media URL, not a public /storage path.
        $this->assertStringContainsString('/media', $photo->url);
    }

    #[Test]
    public function non_doctor_cannot_access_the_derma_workspace(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );
        $user = User::create([
            'name' => 'P', 'email' => 'p-derma@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/doctor/derma')->assertRedirect();
    }
}

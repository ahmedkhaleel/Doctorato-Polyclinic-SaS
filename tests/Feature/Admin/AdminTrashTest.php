<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTrashTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name_en' => 'Super Admin', 'display_name_ar' => 'مدير عام', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->adminUser = User::create([
            'name' => 'Admin Trash', 'email' => 'admin-trash@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_admin_can_view_trash_page(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/admin/trash')
            ->assertOk();
    }

    public function test_trash_page_shows_counts(): void
    {
        // Create and soft-delete a patient
        $patient = new Patient(['full_name' => 'Deleted Patient', 'phone' => '01999000001']);
        $patient->file_number = 'P-TRASH-001';
        $patient->is_active = true;
        $patient->save();
        $patient->delete();

        $response = $this->actingAs($this->adminUser)->get('/admin/trash');
        $response->assertOk();

        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertGreaterThanOrEqual(1, $props['totalCount']);
        $this->assertGreaterThanOrEqual(1, $props['counts']['patients']);
    }

    public function test_can_restore_soft_deleted_patient(): void
    {
        $patient = new Patient(['full_name' => 'Restore Me', 'phone' => '01999000002']);
        $patient->file_number = 'P-TRASH-002';
        $patient->is_active = true;
        $patient->save();
        $patientId = $patient->id;
        $patient->delete();

        // Confirm it's soft-deleted
        $this->assertNull(Patient::find($patientId));
        $this->assertNotNull(Patient::withTrashed()->find($patientId));

        $this->actingAs($this->adminUser)
            ->post("/admin/trash/patients/{$patientId}/restore")
            ->assertRedirect();

        // Confirm it's restored
        $this->assertNotNull(Patient::find($patientId));
    }

    public function test_can_force_delete_patient(): void
    {
        $patient = new Patient(['full_name' => 'Delete Forever', 'phone' => '01999000003']);
        $patient->file_number = 'P-TRASH-003';
        $patient->is_active = true;
        $patient->save();
        $patientId = $patient->id;
        $patient->delete();

        $this->actingAs($this->adminUser)
            ->post("/admin/trash/patients/{$patientId}/delete")
            ->assertRedirect();

        // Confirm it's gone completely
        $this->assertNull(Patient::withTrashed()->find($patientId));
    }

    public function test_can_restore_soft_deleted_doctor(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور محذوف', 'name_en' => 'Deleted Doctor', 'status' => 'active',
        ]);
        $doctorId = $doctor->id;
        $doctor->delete();

        $this->assertNull(Doctor::find($doctorId));

        $this->actingAs($this->adminUser)
            ->post("/admin/trash/doctors/{$doctorId}/restore")
            ->assertRedirect();

        $this->assertNotNull(Doctor::find($doctorId));
    }

    public function test_can_restore_soft_deleted_invoice(): void
    {
        $patient = new Patient(['full_name' => 'Invoice Patient', 'phone' => '01999000004']);
        $patient->file_number = 'P-TRASH-004';
        $patient->is_active = true;
        $patient->save();

        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-TRASH-001',
            'total' => 500, 'invoice_date' => now()->toDateString(),
        ]);
        $invoiceId = $invoice->id;
        $invoice->delete();

        $this->actingAs($this->adminUser)
            ->post("/admin/trash/invoices/{$invoiceId}/restore")
            ->assertRedirect();

        $this->assertNotNull(Invoice::find($invoiceId));
    }

    public function test_can_restore_all_of_type(): void
    {
        // Create and delete 3 patients
        for ($i = 1; $i <= 3; $i++) {
            $p = new Patient(['full_name' => "Bulk Patient {$i}", 'phone' => "0199900010{$i}"]);
            $p->file_number = "P-BULK-00{$i}";
            $p->is_active = true;
            $p->save();
            $p->delete();
        }

        $this->assertEquals(3, Patient::onlyTrashed()->count());

        $this->actingAs($this->adminUser)
            ->post('/admin/trash/patients/restore-all')
            ->assertRedirect();

        $this->assertEquals(0, Patient::onlyTrashed()->count());
    }

    public function test_can_empty_trash_for_type(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $p = new Patient(['full_name' => "Empty Patient {$i}", 'phone' => "0199900020{$i}"]);
            $p->file_number = "P-EMPTY-00{$i}";
            $p->is_active = true;
            $p->save();
            $p->delete();
        }

        $this->actingAs($this->adminUser)
            ->post('/admin/trash/patients/empty')
            ->assertRedirect();

        $this->assertEquals(0, Patient::onlyTrashed()->count());
        $this->assertEquals(0, Patient::withTrashed()->whereNotNull('deleted_at')->count());
    }

    public function test_trash_filter_by_type(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/admin/trash?type=patients')
            ->assertOk();
    }

    public function test_trash_search(): void
    {
        $patient = new Patient(['full_name' => 'Searchable Deleted', 'phone' => '01999000099']);
        $patient->file_number = 'P-SEARCH-001';
        $patient->is_active = true;
        $patient->save();
        $patient->delete();

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/trash?type=patients&search=Searchable');

        $response->assertOk();
        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertNotEmpty($props['trashedData']['patients']);
    }

    public function test_invalid_type_returns_error(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/admin/trash/invalid_type/1/restore')
            ->assertRedirect();
    }

    public function test_restoring_patient_also_restores_user(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );

        $patientUser = User::create([
            'name' => 'Patient User', 'email' => 'trash-patient-user@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $patient = new Patient(['full_name' => 'User Patient', 'phone' => '01999000077']);
        $patient->file_number = 'P-USERTRASH-001';
        $patient->user_id = $patientUser->id;
        $patient->is_active = true;
        $patient->save();

        $patientId = $patient->id;
        $userId = $patientUser->id;

        // Soft-delete both
        $patient->delete();
        $patientUser->delete();

        $this->assertNull(User::find($userId));
        $this->assertNull(Patient::find($patientId));

        // Restore patient — should also restore the user
        $this->actingAs($this->adminUser)
            ->post("/admin/trash/patients/{$patientId}/restore")
            ->assertRedirect();

        $this->assertNotNull(Patient::find($patientId));
        $this->assertNotNull(User::find($userId));
    }

    public function test_soft_deleted_records_hidden_from_normal_queries(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور مخفي', 'name_en' => 'Hidden Doctor', 'status' => 'active',
        ]);
        $doctorId = $doctor->id;
        $doctor->delete();

        // Normal query should not find it
        $this->assertNull(Doctor::find($doctorId));

        // withTrashed should find it
        $this->assertNotNull(Doctor::withTrashed()->find($doctorId));

        // onlyTrashed should find it
        $this->assertNotNull(Doctor::onlyTrashed()->find($doctorId));
    }
}

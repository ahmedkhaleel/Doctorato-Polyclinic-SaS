<?php

namespace Tests\Feature\Admin;

use App\Models\DentalLabOrder;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDentalLabOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-lab@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        // Enable dental module
        DB::table('module_settings')->updateOrInsert(
            ['module' => 'dental', 'key' => 'enabled'],
            ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
        );
        cache()->forget('module_dental_enabled');

        $this->patient = new Patient([
            'full_name' => 'Lab Patient', 'phone' => '0500001001', 'gender' => 'male',
        ]);
        $this->patient->file_number = 'PAT-LAB-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور أسنان', 'name_en' => 'Dental Doctor',
            'specialization_ar' => 'أسنان', 'specialization_en' => 'Dental',
            'department' => 'dental', 'status' => 'active',
        ]);
    }

    public function test_can_view_lab_orders_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/dental/lab-orders')->assertOk();
    }

    public function test_can_create_lab_order(): void
    {
        $this->actingAs($this->admin)->post('/admin/dental/lab-orders', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'item_type' => 'crown',
            'order_date' => '2025-06-01',
            'lab_name' => 'Alpha Lab',
            'material' => 'zirconia',
            'tooth_number' => 11,
            'shade' => 'A2',
            'cost' => 500,
            'patient_charge' => 1500,
        ])->assertRedirect();

        $this->assertDatabaseHas('dental_lab_orders', [
            'patient_id' => $this->patient->id,
            'item_type' => 'crown',
            'status' => 'ordered',
            'lab_name' => 'Alpha Lab',
        ]);
    }

    public function test_lab_order_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/dental/lab-orders', [])
            ->assertSessionHasErrors(['patient_id', 'doctor_id', 'item_type', 'order_date']);
    }

    public function test_item_type_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/admin/dental/lab-orders', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'item_type' => 'invalid_type',
            'order_date' => '2025-06-01',
        ])->assertSessionHasErrors('item_type');
    }

    public function test_can_update_lab_order_status(): void
    {
        // Status transitions: ordered → in_production → ready → delivered
        $order = DentalLabOrder::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'item_type' => 'bridge',
            'order_date' => '2025-06-01',
            'status' => 'ready',
        ]);

        $this->actingAs($this->admin)->post("/admin/dental/lab-orders/{$order->id}", [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'item_type' => 'bridge',
            'order_date' => '2025-06-01',
            'status' => 'delivered',
        ])->assertRedirect();

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_date);
    }

    public function test_can_delete_lab_order(): void
    {
        $order = DentalLabOrder::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'item_type' => 'crown',
            'order_date' => '2025-06-01',
            'status' => 'ordered',
        ]);

        $this->actingAs($this->admin)->post("/admin/dental/lab-orders/{$order->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('dental_lab_orders', ['id' => $order->id]);
    }

    public function test_can_view_lab_dashboard(): void
    {
        $this->actingAs($this->admin)->get('/admin/dental/lab-orders/dashboard')->assertOk();
    }
}

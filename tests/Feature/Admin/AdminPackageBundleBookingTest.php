<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\PackageBundle;
use App\Models\PackageBundleBooking;
use App\Models\PackageBundleService;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the package-bundle BOOKING write path (distinct from bundle
 * definition, covered by AdminPackageBundleTest): creating a booking with
 * per-service doctor assignment and scheduled appointments must persist the
 * booking, its services, its appointments, and generate the bundle invoice.
 */
class AdminPackageBundleBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private Doctor $doctor;

    private PackageBundle $bundle;

    private PackageBundleService $bundleService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-pbb@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Bundle Patient', 'phone' => '0500008001', 'gender' => 'male',
        ]);
        $this->patient->file_number = 'PAT-PBB-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور باقة', 'name_en' => 'Bundle Doctor',
            'specialization_ar' => 'تجميل', 'specialization_en' => 'Cosmetic',
            'department' => 'derma', 'status' => 'active',
        ]);

        $category = ServiceCategory::create([
            'name_ar' => 'تصنيف', 'name_en' => 'Category', 'slug' => 'pbb-cat',
        ]);
        $service = Service::create([
            'name_ar' => 'ليزر', 'name_en' => 'Laser', 'slug' => 'pbb-laser',
            'category_id' => $category->id, 'status' => 'active', 'price' => 500,
        ]);

        $this->bundle = PackageBundle::create([
            'name_ar' => 'باقة', 'name_en' => 'Bundle',
            'total_price' => 1600, 'original_price' => 2000, 'is_active' => true,
        ]);
        $this->bundleService = PackageBundleService::create([
            'package_bundle_id' => $this->bundle->id,
            'service_id' => $service->id,
            'sessions_count' => 4, 'discount_percentage' => 20, 'bundle_price' => 1600,
        ]);
    }

    public function test_bookings_index_renders(): void
    {
        $this->actingAs($this->admin)->get('/admin/package-bundle-bookings')->assertOk();
    }

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin)->get('/admin/package-bundle-bookings/create')->assertOk();
    }

    public function test_create_booking_persists_booking_services_appointments_and_invoice(): void
    {
        $date = now()->addDays(3)->toDateString();

        $this->actingAs($this->admin)->post('/admin/package-bundle-bookings', [
            'package_bundle_id' => $this->bundle->id,
            'patient_id' => $this->patient->id,
            'services' => [
                [
                    'package_bundle_service_id' => $this->bundleService->id,
                    'doctor_id' => $this->doctor->id,
                    'appointments' => [
                        ['date' => $date, 'start_time' => '10:00', 'end_time' => '10:30'],
                        ['date' => $date, 'start_time' => '11:00', 'end_time' => '11:30'],
                    ],
                ],
            ],
        ])->assertRedirect();

        $booking = PackageBundleBooking::where('patient_id', $this->patient->id)->firstOrFail();

        // Booking header: confirmed, priced from the bundle, full balance due.
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals(1600.0, (float) $booking->total_price);
        $this->assertEquals(1600.0, (float) $booking->balance_due);

        // One booking service with the assigned doctor + correct session count.
        $this->assertDatabaseHas('package_bundle_booking_services', [
            'package_bundle_booking_id' => $booking->id,
            'package_bundle_service_id' => $this->bundleService->id,
            'doctor_id' => $this->doctor->id,
            'sessions_count' => 4,
        ]);

        // Two scheduled appointments.
        $this->assertEquals(
            2,
            $booking->appointments()->where('status', 'scheduled')->count(),
            'both requested appointments should be scheduled'
        );

        // Bundle invoice generated and linked.
        $this->assertDatabaseHas('invoices', [
            'package_bundle_booking_id' => $booking->id,
            'total' => 1600,
            'status' => 'unpaid',
        ]);
    }

    public function test_create_booking_requires_at_least_one_service(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/package-bundle-bookings/create')
            ->post('/admin/package-bundle-bookings', [
                'package_bundle_id' => $this->bundle->id,
                'patient_id' => $this->patient->id,
                'services' => [],
            ])
            ->assertSessionHasErrors('services');

        $this->assertDatabaseMissing('package_bundle_bookings', [
            'patient_id' => $this->patient->id,
        ]);
    }
}

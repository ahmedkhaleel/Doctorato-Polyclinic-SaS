<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\SpecialtyDoctorDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The per-specialty demo seeder must produce a login + a complete, self-contained
 * data set per medical module, and be idempotent.
 */
class SpecialtyDoctorDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private array $modules = ['dental', 'derma', 'pediatric', 'obgyn', 'psychiatry', 'neurology', 'physiotherapy'];

    public function test_seeds_a_login_and_full_dataset_per_specialty(): void
    {
        $this->seed(SpecialtyDoctorDemoSeeder::class);

        foreach ($this->modules as $m) {
            $user = User::where('email', "demo.{$m}@doctorato.net")->first();
            $this->assertNotNull($user, "login for {$m}");

            $doctor = Doctor::where('user_id', $user->id)->where('module', $m)->first();
            $this->assertNotNull($doctor, "doctor for {$m}");

            // Completed visits with commission + invoice + payment.
            $visits = Visit::where('doctor_id', $doctor->id)->where('module', $m)->get();
            $this->assertGreaterThanOrEqual(3, $visits->count(), "{$m} visits");
            $this->assertTrue($visits->every(fn ($v) => $v->status === 'completed'));
            $withCommission = $visits->filter(fn ($v) => (float) $v->commission_amount > 0)->count();
            $this->assertGreaterThanOrEqual(3, $withCommission, "{$m} commissions");

            $invoiceCount = Invoice::where('module', $m)->count();
            $this->assertGreaterThanOrEqual(3, $invoiceCount, "{$m} invoices");
        }

        // Payments exist overall.
        $this->assertGreaterThanOrEqual(18, Payment::count());

        // Spot-check specialty clinical records.
        $this->assertGreaterThan(0, \App\Models\DentalTreatmentPlan::count());
        $this->assertGreaterThan(0, \App\Models\Pregnancy::count());
        $this->assertGreaterThan(0, \App\Models\ScaleResult::where('scale_key', 'phq9')->count());
        $this->assertGreaterThan(0, \App\Models\PediatricGrowthRecord::count());
        // Physiotherapy demo data: plan, assessment, sessions, HEP + ODI PROMs.
        $this->assertGreaterThan(0, \App\Models\PhysioTreatmentPlan::count());
        $this->assertGreaterThan(0, \App\Models\PhysioSession::count());
        $this->assertGreaterThan(0, \App\Models\ScaleResult::where('scale_key', 'odi')->count());
    }

    public function test_is_idempotent(): void
    {
        $this->seed(SpecialtyDoctorDemoSeeder::class);
        $visitsAfterFirst = Visit::count();
        $usersAfterFirst = User::where('email', 'like', 'demo.%@doctorato.net')->count();

        $this->seed(SpecialtyDoctorDemoSeeder::class);

        $this->assertSame($visitsAfterFirst, Visit::count(), 'visits must not duplicate');
        $this->assertSame($usersAfterFirst, User::where('email', 'like', 'demo.%@doctorato.net')->count());
    }
}

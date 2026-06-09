<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Visit;
use Database\Seeders\MonthlyAppointmentsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fills the current month with appointments per doctor in three states:
 * completed (past) + in_progress/waiting (today) + confirmed upcoming (future).
 */
class MonthlyAppointmentsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_three_states_across_the_month(): void
    {
        Role::firstOrCreate(['name' => 'super_admin'], ['display_name_en' => 'SA', 'display_name_ar' => 'SA', 'permissions' => ['*'], 'is_system' => true]);
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'status' => 'active', 'module' => 'derma', 'default_commission_percentage' => 25]);
        $p = Patient::create(['full_name' => 'P', 'phone' => '0599999999']);
        $p->forceFill(['is_active' => true, 'file_number' => 'PAT-MO-1'])->save();

        $this->seed(MonthlyAppointmentsDemoSeeder::class);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        // Completed visits with commission billed.
        $completed = Visit::where('doctor_id', $doctor->id)->where('status', 'completed')->get();
        $this->assertGreaterThan(0, $completed->count(), 'has completed visits');
        $this->assertTrue($completed->every(fn ($v) => (float) $v->commission_amount > 0));

        // In-progress + waiting on/around today.
        $this->assertGreaterThan(0, Visit::where('doctor_id', $doctor->id)->whereIn('status', ['in_progress', 'waiting'])->count(), 'has in-progress/waiting');

        // Future confirmed appointments with NO visit yet (not executed).
        $futureBookings = Booking::where('doctor_id', $doctor->id)->where('source', 'monthly_demo')
            ->whereDate('preferred_date', '>', now()->toDateString())->get();
        $this->assertGreaterThan(0, $futureBookings->count(), 'has upcoming appointments');
        $this->assertSame(0, Visit::whereIn('booking_id', $futureBookings->pluck('id'))->count(), 'upcoming have no visit yet');

        // Bookings span the whole month.
        $this->assertTrue(Booking::where('doctor_id', $doctor->id)->where('source', 'monthly_demo')->whereBetween('preferred_date', [$start, $end])->count() >= 10);
    }

    public function test_is_idempotent(): void
    {
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc2', 'status' => 'active', 'module' => 'dental']);
        Patient::create(['full_name' => 'P2', 'phone' => '0598888888'])->forceFill(['is_active' => true, 'file_number' => 'PAT-MO-2'])->save();

        $this->seed(MonthlyAppointmentsDemoSeeder::class);
        $count = Booking::where('source', 'monthly_demo')->count();
        $this->seed(MonthlyAppointmentsDemoSeeder::class);
        $this->assertSame($count, Booking::where('source', 'monthly_demo')->count(), 're-run must not duplicate');
    }
}

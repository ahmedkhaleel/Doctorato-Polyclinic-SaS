<?php

namespace Tests\Feature\Physio;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-C — a doctor schedules a recurring physiotherapy session series; each
 * occurrence becomes a real physiotherapy_session booking spaced by interval.
 */
class PhysiotherapySessionSeriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_a_series_creates_weekly_session_bookings(): void
    {
        ModuleManager::flushStaticCache();
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $user = User::create(['name' => 'PT', 'email' => 'series-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT', 'user_id' => $user->id, 'status' => 'active', 'module' => 'physiotherapy']);
        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();

        $patient = Patient::create(['full_name' => 'Series P', 'phone' => '0500003434']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-SR-1'])->save();

        $start = now()->addDay()->toDateString();
        $this->actingAs($user)
            ->post("/doctor/physiotherapy/patients/{$patient->id}/session-series", [
                'start_date' => $start, 'start_time' => '10:00', 'occurrences' => 4, 'interval_days' => 7,
            ])->assertRedirect();

        $bookings = Booking::where('patient_id', $patient->id)->where('booking_type', 'physiotherapy_session')->get();
        $this->assertCount(4, $bookings);
        $this->assertTrue($bookings->every(fn ($b) => $b->module === 'physiotherapy'));
        // Appointments are spaced a week apart.
        $dates = $bookings->flatMap(fn ($b) => $b->appointments->pluck('appointment_date'))->map(fn ($d) => $d->toDateString())->sort()->values();
        $this->assertSame($start, $dates->first());
        $this->assertSame(now()->addDay()->addWeeks(3)->toDateString(), $dates->last());
    }
}

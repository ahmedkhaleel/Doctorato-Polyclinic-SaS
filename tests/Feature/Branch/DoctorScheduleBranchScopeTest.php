<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorScheduleBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function doctor(): Doctor
    {
        return Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active']);
    }

    private function schedule(Doctor $d, int $day): DoctorSchedule
    {
        return DoctorSchedule::create([
            'doctor_id' => $d->id, 'day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '17:00',
            'is_active' => true, 'mode' => 'in_person', 'slot_duration_minutes' => 30, 'buffer_minutes' => 0,
        ]);
    }

    public function test_disabled_stamps_main(): void
    {
        config(['branches.enabled' => false]);
        $this->assertSame(1, (int) $this->schedule($this->doctor(), 1)->branch_id);
    }

    public function test_doctor_can_have_schedules_in_two_branches(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $d = $this->doctor();

        $this->ctx()->set(1);
        $this->schedule($d, 1); // Sunday at branch 1
        $this->ctx()->runForBranch(2, fn () => $this->schedule($d, 1)); // Sunday at branch 2

        $this->ctx()->set(1);
        $this->assertSame(1, DoctorSchedule::count());
        $this->ctx()->set(2);
        $this->assertSame(1, DoctorSchedule::count());
        $this->ctx()->setAllBranches();
        $this->assertSame(2, DoctorSchedule::count()); // same doctor, same day, two branches
    }
}

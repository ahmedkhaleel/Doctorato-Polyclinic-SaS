<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\Branch\BranchContext;
use App\Services\BookingWorkflowService;
use App\Services\TimeSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingBranchSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    public function test_website_booking_attributed_to_selected_branch(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $booking = app(BookingWorkflowService::class)->createFromWebsite([
            'full_name' => 'Web Patient', 'phone' => '01099887766',
            'booking_type' => 'service', 'module' => 'dental',
            'preferred_date' => now()->addDay()->toDateString(),
            'branch_id' => $b2->id,
        ]);

        $this->assertSame($b2->id, (int) $booking->branch_id);
        $this->assertStringStartsWith('BK-B2-', $booking->booking_number);
    }

    public function test_no_branch_id_defaults_to_main(): void
    {
        config(['branches.enabled' => true]);
        $booking = app(BookingWorkflowService::class)->createFromWebsite([
            'full_name' => 'Web Patient', 'phone' => '01099887700',
            'booking_type' => 'service', 'module' => 'dental',
            'preferred_date' => now()->addDay()->toDateString(),
        ]);
        $this->assertSame(1, (int) $booking->branch_id);
    }

    public function test_slots_reflect_only_the_selected_branch_schedule(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $doc = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active']);
        $doc->branches()->attach($b2->id);

        // Pick a date 3 days out; map Carbon dow (0=Sun) → system dow (0=Sat).
        $date = Carbon::today()->addDays(3);
        $systemDay = ($date->dayOfWeek + 1) % 7;

        // Schedule ONLY in branch 2 for that weekday.
        $this->ctx()->runForBranch($b2->id, fn () => DoctorSchedule::create([
            'doctor_id' => $doc->id, 'day_of_week' => $systemDay, 'start_time' => '09:00', 'end_time' => '13:00',
            'is_active' => true, 'mode' => 'in_person', 'slot_duration_minutes' => 30, 'buffer_minutes' => 0,
        ]));

        $svc = app(TimeSlotService::class);
        $branch2Slots = $this->ctx()->runForBranch($b2->id, fn () => $svc->getAvailableSlots($doc->id, $date->toDateString(), 30));
        $branch1Slots = $this->ctx()->runForBranch(1, fn () => $svc->getAvailableSlots($doc->id, $date->toDateString(), 30));

        $this->assertNotEmpty($branch2Slots, 'branch 2 has a schedule → slots');
        $this->assertEmpty($branch1Slots, 'branch 1 has no schedule → no slots');
    }
}

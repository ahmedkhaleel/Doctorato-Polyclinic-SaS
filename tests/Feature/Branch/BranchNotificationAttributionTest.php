<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Patient;
use App\Services\Branch\BranchContext;
use App\Services\Notifications\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The notification hub is a single org-wide sender (no branch scope), but its
 * logs are attributed to the branch of the triggering record for per-branch
 * analytics — even when dispatched from cron/queue (all-branches mode).
 */
class BranchNotificationAttributionTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'N', 'phone' => '01012345678']);
        $p->file_number = 'P-NA-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    public function test_log_is_attributed_to_payload_models_branch(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $patient = $this->patient();

        $booking = app(BranchContext::class)->runForBranch($b2->id, fn () => Booking::create([
            'patient_id' => $patient->id, 'full_name' => $patient->full_name, 'phone' => $patient->phone,
            'booking_type' => 'service', 'status' => 'confirmed', 'source' => 'website',
            'module' => 'dental', 'preferred_date' => now()->toDateString(),
        ]));
        $this->assertSame($b2->id, (int) $booking->branch_id);

        // in_app is enabled by the seed migration; dispatch carries the booking.
        $logs = Notifier::eventNow('payment.received', $patient, ['body' => 'تم', 'booking' => $booking]);

        $inApp = collect($logs)->firstWhere('channel', 'in_app');
        $this->assertNotNull($inApp);
        $this->assertSame($b2->id, (int) $inApp->branch_id, 'log inherits the booking branch');
    }
}

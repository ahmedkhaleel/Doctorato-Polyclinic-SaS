<?php

namespace App\Console\Commands;

use App\Models\DentalFollowupRule;
use App\Models\DentalScheduledFollowup;
use App\Models\Setting;
use App\Services\DentalFollowupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDentalFollowups extends Command
{
    protected $signature = 'dental:process-followups
                            {--dry-run : Show what would be done without executing}';

    protected $description = 'Process scheduled dental follow-ups: create bookings and send SMS reminders';

    public function handle(DentalFollowupService $service): int
    {
        if (Setting::get('dental_auto_followup_enabled', '1') !== '1') {
            $this->warn('Auto follow-up is disabled. Skipping.');
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $totalActions = 0;

        // 1. Create bookings for pending follow-ups that don't have one yet
        $totalActions += $this->createPendingBookings($service, $dryRun);

        // 2. Send initial SMS for follow-ups that have booking but no SMS sent
        $totalActions += $this->sendInitialSms($service, $dryRun);

        // 3. Send day-before SMS reminders
        $totalActions += $this->sendDayBeforeReminders($service, $dryRun);

        // 4. Auto-complete follow-ups whose date has passed
        $totalActions += $this->autoCompleteExpired($dryRun);

        $prefix = $dryRun ? '[DRY RUN]' : '';
        $this->info("{$prefix} Dental follow-up processing complete: {$totalActions} actions.");

        return self::SUCCESS;
    }

    /**
     * Create bookings for pending follow-ups without a booking.
     */
    protected function createPendingBookings(DentalFollowupService $service, bool $dryRun): int
    {
        $followups = DentalScheduledFollowup::with(['treatment.patient', 'treatment.doctor', 'rule'])
            ->needsBooking()
            ->where('scheduled_date', '>=', today())
            ->whereHas('rule', fn ($q) => $q->where('auto_create_booking', true)->where('is_active', true))
            ->get();

        $count = 0;

        foreach ($followups as $followup) {
            if (!$followup->treatment) continue;

            if ($dryRun) {
                $this->line("  [DRY] Create booking: {$followup->treatment->patient?->full_name} → {$followup->scheduled_date->format('Y-m-d')}");
                $count++;
                continue;
            }

            $booking = $service->createFollowupBooking($followup, $followup->treatment, $followup->rule);
            if ($booking) {
                $count++;
            }
        }

        $this->line("  → Bookings created: {$count}");
        return $count;
    }

    /**
     * Send initial SMS to patients about their scheduled follow-up.
     */
    protected function sendInitialSms(DentalFollowupService $service, bool $dryRun): int
    {
        if (!$this->smsEnabled()) {
            return 0;
        }

        $followups = DentalScheduledFollowup::with(['patient', 'rule', 'doctor'])
            ->whereIn('status', ['pending', 'booking_created'])
            ->whereNull('sms_sent_at')
            ->where('scheduled_date', '>=', today())
            ->whereHas('rule', fn ($q) => $q->where('sms_patient', true)->where('is_active', true))
            ->get();

        $count = 0;

        foreach ($followups as $followup) {
            if ($dryRun) {
                $this->line("  [DRY] Send scheduled SMS: {$followup->patient?->full_name} → {$followup->scheduled_date->format('Y-m-d')}");
                $count++;
                continue;
            }

            $result = $service->sendScheduledSms($followup);
            if ($result['success'] ?? false) {
                $count++;
            }
        }

        $this->line("  → Initial SMS sent: {$count}");
        return $count;
    }

    /**
     * Send day-before reminder SMS.
     */
    protected function sendDayBeforeReminders(DentalFollowupService $service, bool $dryRun): int
    {
        if (!$this->smsEnabled()) {
            return 0;
        }

        // Get unique sms_days_before values from active rules
        $daysBefore = DentalFollowupRule::active()
            ->where('sms_patient', true)
            ->distinct()
            ->pluck('sms_days_before');

        $count = 0;

        foreach ($daysBefore as $days) {
            $followups = DentalScheduledFollowup::with(['patient', 'rule', 'doctor'])
                ->needsSmsReminder($days)
                ->whereHas('rule', fn ($q) => $q->where('sms_patient', true)->where('is_active', true))
                ->get();

            foreach ($followups as $followup) {
                if ($dryRun) {
                    $this->line("  [DRY] Reminder SMS ({$days}d before): {$followup->patient?->full_name} → {$followup->scheduled_date->format('Y-m-d')}");
                    $count++;
                    continue;
                }

                $result = $service->sendSmsReminder($followup);
                if ($result['success'] ?? false) {
                    $count++;
                }
            }
        }

        $this->line("  → Day-before reminders: {$count}");
        return $count;
    }

    /**
     * Auto-complete follow-ups whose scheduled date has passed.
     */
    protected function autoCompleteExpired(bool $dryRun): int
    {
        $followups = DentalScheduledFollowup::whereIn('status', ['pending', 'booking_created', 'sms_sent'])
            ->where('scheduled_date', '<', today()->subDays(3))
            ->get();

        $count = 0;

        foreach ($followups as $followup) {
            if ($dryRun) {
                $this->line("  [DRY] Auto-complete expired: #{$followup->id} ({$followup->scheduled_date->format('Y-m-d')})");
                $count++;
                continue;
            }

            $followup->update(['status' => 'completed']);
            $count++;
        }

        $this->line("  → Expired follow-ups completed: {$count}");
        return $count;
    }

    /**
     * Check if SMS is enabled globally.
     */
    protected function smsEnabled(): bool
    {
        return Setting::get('sms_enabled', '0') === '1';
    }
}

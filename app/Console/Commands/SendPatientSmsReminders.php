<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\SmsNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPatientSmsReminders extends Command
{
    protected $signature = 'patients:send-sms-reminders
                            {--type=all : Type of reminder (day-before, same-day, all)}
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send SMS reminders to patients for upcoming bookings (day before + same day)';

    public function handle(): int
    {
        if (Setting::get('sms_enabled', '0') !== '1') {
            $this->warn('SMS is not enabled. Skipping patient reminders.');
            return self::SUCCESS;
        }

        $type = $this->option('type');
        $dryRun = $this->option('dry-run');
        $totalSent = 0;

        if (in_array($type, ['day-before', 'all'])) {
            $totalSent += $this->sendDayBeforeReminders($dryRun);
        }

        if (in_array($type, ['same-day', 'all'])) {
            $totalSent += $this->sendSameDayReminders($dryRun);
        }

        $prefix = $dryRun ? '[DRY RUN] Would send' : 'Sent';
        $this->info("{$prefix} {$totalSent} patient SMS reminders.");

        return self::SUCCESS;
    }

    /**
     * Send reminders to patients with confirmed bookings for tomorrow.
     */
    protected function sendDayBeforeReminders(bool $dryRun): int
    {
        if (Setting::get('sms_reminder_day_before', '1') !== '1') {
            $this->line('  Day-before reminders disabled in settings. Skipping.');
            return 0;
        }

        $bookings = Booking::with(['patient', 'service', 'doctor'])
            ->where('status', 'confirmed')
            ->whereDate('preferred_date', today()->addDay())
            ->whereNull('reminder_sms_sent_at')
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            $phone = $booking->phone ?? $booking->patient?->phone;
            if (! $phone) {
                continue;
            }

            if ($dryRun) {
                $name = $booking->full_name ?? $booking->patient?->full_name ?? 'Unknown';
                $this->line("  [DRY] Day-before: {$name} ({$phone}) - {$booking->preferred_date->format('Y-m-d')} {$booking->preferred_time}");
                $count++;
                continue;
            }

            try {
                $result = SmsNotificationService::bookingReminder($booking);
                if ($result['success'] ?? false) {
                    $booking->update(['reminder_sms_sent_at' => now()]);
                    $count++;
                } else {
                    Log::warning('Day-before SMS reminder failed', [
                        'booking_id' => $booking->id,
                        'message' => $result['message'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Day-before SMS reminder exception', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->line("  Day-before reminders: {$count}/{$bookings->count()}");
        return $count;
    }

    /**
     * Send same-day morning reminders to patients with confirmed bookings today.
     */
    protected function sendSameDayReminders(bool $dryRun): int
    {
        if (Setting::get('sms_reminder_same_day', '0') !== '1') {
            $this->line('  Same-day reminders disabled in settings. Skipping.');
            return 0;
        }

        $bookings = Booking::with(['patient', 'service', 'doctor'])
            ->where('status', 'confirmed')
            ->whereDate('preferred_date', today())
            ->whereNull('sameday_sms_sent_at')
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            $phone = $booking->phone ?? $booking->patient?->phone;
            if (! $phone) {
                continue;
            }

            if ($dryRun) {
                $name = $booking->full_name ?? $booking->patient?->full_name ?? 'Unknown';
                $this->line("  [DRY] Same-day: {$name} ({$phone}) - {$booking->preferred_time}");
                $count++;
                continue;
            }

            try {
                $result = SmsNotificationService::sameDayReminder($booking);
                if ($result['success'] ?? false) {
                    $booking->update(['sameday_sms_sent_at' => now()]);
                    $count++;
                } else {
                    Log::warning('Same-day SMS reminder failed', [
                        'booking_id' => $booking->id,
                        'message' => $result['message'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Same-day SMS reminder exception', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->line("  Same-day reminders: {$count}/{$bookings->count()}");
        return $count;
    }
}

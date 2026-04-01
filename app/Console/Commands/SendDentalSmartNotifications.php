<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\DentalSmartNotificationService;
use Illuminate\Console\Command;

class SendDentalSmartNotifications extends Command
{
    protected $signature = 'dental:smart-notifications
                            {--scan-only : Only scan for new notifications, don\'t send}
                            {--send-only : Only send pending, don\'t scan}
                            {--dry-run : Preview without sending}';

    protected $description = 'Scan for and send smart patient notifications (follow-up, lab order, stalled plans, post-treatment, pre-appointment alerts)';

    public function handle(): int
    {
        if (Setting::get('dental_smart_notifications_enabled', '1') !== '1') {
            $this->warn('Smart patient notifications are disabled.');
            return self::SUCCESS;
        }

        $scanOnly = $this->option('scan-only');
        $sendOnly = $this->option('send-only');
        $dryRun = $this->option('dry-run');

        $this->info('🔔 Dental Smart Notifications');
        $this->line('─────────────────────────────');

        $totalScanned = 0;
        $totalSent = 0;
        $totalFailed = 0;

        // ── Step 1: Scan for new notifications ──────────
        if (!$sendOnly) {
            $this->line('');
            $this->info('📡 Scanning for notification triggers...');

            $followup = DentalSmartNotificationService::scanFollowUpReminders();
            $this->line("  → Follow-up reminders queued: {$followup}");
            $totalScanned += $followup;

            $stalled = DentalSmartNotificationService::scanStalledPlans();
            $this->line("  → Stalled plan reminders queued: {$stalled}");
            $totalScanned += $stalled;

            $preAppointment = DentalSmartNotificationService::scanPreAppointmentAlerts();
            $this->line("  → Pre-appointment medical alerts queued: {$preAppointment}");
            $totalScanned += $preAppointment;

            $this->line("  ✓ Total new notifications queued: {$totalScanned}");
        }

        // ── Step 2: Send pending notifications ──────────
        if (!$scanOnly && !$dryRun) {
            $this->line('');
            $this->info('📤 Sending pending notifications...');

            $sendResults = DentalSmartNotificationService::sendPendingNotifications();
            $totalSent = $sendResults['sent'];
            $totalFailed = $sendResults['failed'];

            $this->line("  → Sent successfully: {$totalSent}");
            if ($totalFailed > 0) {
                $this->warn("  → Failed: {$totalFailed}");
            }
        }

        // ── Summary ─────────────────────────────────────
        $this->line('');
        $this->line('─────────────────────────────');
        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. Scanned: {$totalScanned} | Sent: {$totalSent} | Failed: {$totalFailed}");

        return self::SUCCESS;
    }
}

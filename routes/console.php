<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send booking reminders to doctors every day at 7:00 AM
Schedule::command('bookings:send-reminders')->dailyAt('07:00');

// Auto-create visits for today's appointments every day at 8:00 AM
Schedule::command('bookings:create-daily-visits')->dailyAt('08:00');

// CRM: Send follow-up reminders every 30 minutes during working hours
Schedule::command('leads:remind-follow-ups')->everyThirtyMinutes()->between('08:00', '20:00');

// CRM: Check for dormant leads daily at midnight
Schedule::command('leads:check-dormant')->dailyAt('00:00');

// CRM: Process follow-up automation sequences every 5 minutes during working hours
Schedule::command('sequences:process')->everyFiveMinutes()->between('08:00', '22:00');

// CRM: Send daily lead report to admin at 9:00 AM
Schedule::command('leads:daily-report')->dailyAt('09:00');

// Dental: Check for overdue lab orders, appointment reminders, and treatment plan deadlines
Schedule::command('dental:check-alerts')->dailyAt('07:30');

// Patient SMS: Send day-before reminders at 6:00 PM
Schedule::command('patients:send-sms-reminders --type=day-before')->dailyAt('18:00');

// Patient SMS: Send same-day reminders at 8:00 AM
Schedule::command('patients:send-sms-reminders --type=same-day')->dailyAt('08:00');

// Patient SMS: Send recall reminders (periodic checkup) at 10:00 AM on Sundays
Schedule::command('patients:send-recall-reminders')->weeklyOn(0, '10:00');

// Dental: Process auto follow-up scheduling (create bookings + send SMS) daily at 8:30 AM
Schedule::command('dental:process-followups')->dailyAt('08:30');

// Dental: Smart patient notifications (follow-up, lab, stalled plans, post-treatment) every 2 hours during working hours
Schedule::command('dental:smart-notifications')->everyTwoHours()->between('08:00', '20:00');

// Telemedicine: Auto-mark missed sessions every 15 minutes
Schedule::command('telemedicine:handle-missed')->everyFifteenMinutes();

// Dental: Auto-expire pending treatment plan consents daily at midnight
Schedule::command('dental:expire-consents')->dailyAt('00:30');

// Loyalty: Tombstone expired earn points so patient history shows them.
// Doesn't change balance (expired points already drop out via the filter)
// — this run just makes the expiry visible in /patient/loyalty history.
Schedule::command('loyalty:expire-points')->dailyAt('00:45')->withoutOverlapping();

// Pediatric: Send upcoming vaccination reminders (7 days ahead) at 9:00 AM
Schedule::command('pediatric:vaccination-reminders --type=upcoming --days=7')->dailyAt('09:00');

// Pediatric: Send overdue vaccination alerts weekly on Tuesdays at 10:30 AM
Schedule::command('pediatric:vaccination-reminders --type=overdue --days=14')->weeklyOn(2, '10:30');

// OB/GYN: antenatal-visit reminders + EDD-approaching alerts daily 09:30; pap recall daily 10:00.
Schedule::command('obgyn:reminders --type=anc')->dailyAt('09:30');
Schedule::command('obgyn:reminders --type=edd')->dailyAt('09:30');
Schedule::command('obgyn:reminders --type=pap')->dailyAt('10:00');

// Inventory: Check low stock and auto-generate purchase orders daily at 9:00 AM
Schedule::command('inventory:check-low-stock')->dailyAt('09:00');

// Insurance: expire approved pre-authorizations past their validity, daily at 00:20.
Schedule::command('insurance:expire-pre-auths')->dailyAt('00:20')->withoutOverlapping();

// HR: Payroll close reminder on the last 2 days of each month at 09:00.
// Lists employees still missing a salary slip so payroll can be closed before month-end.
Schedule::command('hr:payroll-reminder')
    ->dailyAt('09:00')
    ->when(fn () => now()->daysInMonth - now()->day < 2)
    ->withoutOverlapping();

// Satisfaction: Send survey SMS to patients who completed visits yesterday at 10:00 AM
Schedule::command('satisfaction:send-surveys')->dailyAt('10:00');

// Invoice: Check for overdue invoices (7+ days) daily at 11:00 AM
Schedule::command('invoices:check-overdue --days=7')->dailyAt('11:00');

// Notifications Hub: retry recently-failed WhatsApp/SMS/Email deliveries
// every 30 min during waking hours (up to 3 attempts per message).
Schedule::command('notifications:retry')->everyThirtyMinutes()->between('07:00', '23:00')->withoutOverlapping();

// Notifications Hub: dispatch scheduled marketing campaigns when they fall due
// (every 5 min during waking hours; respects quiet hours + consent downstream).
Schedule::command('notifications:dispatch-campaigns')->everyFiveMinutes()->between('08:00', '21:00')->withoutOverlapping();

// Notifications Hub: release notifications held by quiet hours once their window
// opens — runs every 15 min around the clock (quiet windows close at any hour).
Schedule::command('notifications:dispatch-scheduled')->everyFifteenMinutes()->withoutOverlapping();

// ─── Backup ──────────────────────────────────────────────────────────

// Full backup (DB + files) daily at 2:00 AM
Schedule::command('backup:run')->dailyAt('02:00')
    ->onFailure(fn () => \Illuminate\Support\Facades\Log::error('Scheduled backup failed'))
    ->withoutOverlapping();

// DB-only backup every 6 hours (lightweight safety net)
Schedule::command('backup:run --only-db')->everySixHours()
    ->withoutOverlapping();

// Cleanup old backups daily at 3:00 AM
Schedule::command('backup:clean')->dailyAt('03:00');

// Monitor backup health daily at 9:30 AM
Schedule::command('backup:monitor')->dailyAt('09:30');

// Health alert: check /health every 15 minutes, email admin on degraded
// subsystems (payment gateway down, DB unreachable, etc). Internal
// 1-hour cooldown prevents alert spam for the same fingerprint.
Schedule::command('health:alert')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Data integrity sweep: weekly scan for orphans, drift, and stuck rows.
// Writes findings to laravel.log and emails admin if anything is found
// (24h cooldown keeps the inbox sane).
Schedule::command('data:integrity-check --log --alert')
    ->weeklyOn(1, '05:00')   // Mondays at 05:00
    ->withoutOverlapping();

// Reclaim slots from abandoned online consultations (payment never
// completed). Runs hourly so patient-visible availability stays fresh.
Schedule::command('telemedicine:cleanup-stuck')
    ->hourlyAt(10)
    ->withoutOverlapping();

// Recovery email — gives the patient one chance to come back and pay
// before cleanup-stuck reclaims the slot. Runs every 30 minutes; only
// hits each consultation once (recovery_email_sent_at), only fires
// after a 30-minute settling window, and stops chasing after 12 h.
Schedule::command('telemedicine:send-recovery-emails')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Force-delete soft-deleted bookings/visits/invoices/payments/prescriptions
// older than 90 days so the trash table doesn't grow without bound.
// Patients + Doctors intentionally excluded (would break visit/invoice
// foreign keys to historical data).
Schedule::command('trash:prune --days=90')
    ->weeklyOn(2, '03:30')   // Tuesdays 03:30
    ->withoutOverlapping();

// Regenerate /sitemap.xml so search engines see new services / posts /
// pages added through the admin UI without a manual rebuild.
Schedule::command('sitemap:generate')->dailyAt('06:00');

// Weekly operations summary — emailed every Monday 08:00 to the admin,
// collates bookings + revenue + telemedicine + system-blocker state
// into one digest so the operator doesn't have to open five pages.
Schedule::command('reports:weekly')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping();

// ─── Housekeeping ─────────────────────────────────────────────────────

// Prune Laravel logs older than 14 days so storage/logs/*.log doesn't
// grow forever on long-running hosts. Keeps recent logs for debugging.
Schedule::call(function () {
    $cutoff = now()->subDays(14);
    foreach (glob(storage_path('logs/*.log')) as $file) {
        if (filemtime($file) < $cutoff->timestamp) {
            @unlink($file);
        }
    }
})->dailyAt('04:00')->name('prune-old-logs')->onOneServer();

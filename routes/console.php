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

// Dental: Auto-expire pending treatment plan consents daily at midnight
Schedule::command('dental:expire-consents')->dailyAt('00:30');

// Inventory: Check low stock and auto-generate purchase orders daily at 9:00 AM
Schedule::command('inventory:check-low-stock')->dailyAt('09:00');

// Satisfaction: Send survey SMS to patients who completed visits yesterday at 10:00 AM
Schedule::command('satisfaction:send-surveys')->dailyAt('10:00');

// Invoice: Check for overdue invoices (7+ days) daily at 11:00 AM
Schedule::command('invoices:check-overdue --days=7')->dailyAt('11:00');

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

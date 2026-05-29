<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\SalarySlip;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a payroll-close reminder to admin/super_admin users on the last
 * two days of the month: lists employees who still have no salary slip
 * for the current month so payroll managers can action it before month-end.
 *
 * Usage: php artisan hr:payroll-reminder
 * Scheduled: last 2 days of every month at 09:00.
 */
class HrPayrollReminderCommand extends Command
{
    protected $signature   = 'hr:payroll-reminder';
    protected $description = 'Remind admins to close payroll — lists employees missing a slip for this month.';

    public function handle(): int
    {
        $month = now()->month;
        $year  = now()->year;

        // Employees who do NOT yet have a slip for the current month.
        $slippedIds = SalarySlip::forPeriod($month, $year)->pluck('employee_id');

        $missing = \App\Models\Employee::active()
            ->whereNotIn('id', $slippedIds)
            ->with('user:id,name')
            ->get();

        if ($missing->isEmpty()) {
            $this->info("✅ All employees have salary slips for {$month}/{$year} — nothing to do.");
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Payroll reminder: {$missing->count()} employee(s) missing a slip for {$month}/{$year}:");
        foreach ($missing as $emp) {
            $this->line("  - " . ($emp->user?->name ?? "Employee #{$emp->id}"));
        }

        // Notify admin/super_admin users via the app notification system.
        $adminRoles = Role::whereIn('name', ['admin', 'super_admin'])->pluck('id');
        $admins = User::whereIn('role_id', $adminRoles)->where('is_active', true)->get();

        if ($admins->isNotEmpty()) {
            try {
                Notification::send($admins, new \App\Notifications\HrPayrollReminder($missing, $month, $year));
            } catch (\Throwable $e) {
                $this->warn("Notification failed: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}

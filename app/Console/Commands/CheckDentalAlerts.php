<?php

namespace App\Console\Commands;

use App\Models\DentalLabOrder;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\User;
use App\Models\Visit;
use App\Notifications\DentalAppointmentReminderNotification;
use App\Notifications\DentalFollowUpReminderNotification;
use App\Notifications\DentalLabOrderOverdueNotification;
use App\Notifications\DentalTreatmentPlanDueNotification;
use Illuminate\Console\Command;

class CheckDentalAlerts extends Command
{
    protected $signature = 'dental:check-alerts';

    protected $description = 'Check for overdue dental lab orders, upcoming dental appointments, follow-up reminders, and due treatment plans';

    public function handle(): int
    {
        $adminUsers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))
            ->where('is_active', true)
            ->get();

        $secretaryUsers = User::whereHas('role', fn ($q) => $q->where('name', 'secretary'))
            ->where('is_active', true)
            ->get();

        $staffUsers = $adminUsers->merge($secretaryUsers)->unique('id');

        if ($staffUsers->isEmpty()) {
            $this->warn('No active staff users found to notify.');
            return self::SUCCESS;
        }

        $totalAlerts = 0;

        // 1. Overdue Lab Orders → admin + secretary + doctor
        $totalAlerts += $this->checkOverdueLabOrders($staffUsers);

        // 2. Today's Dental Appointments → doctor + secretary
        $totalAlerts += $this->checkDentalAppointments($secretaryUsers);

        // 3. Follow-up Reminders (7 days after completed treatment) → secretary + doctor
        $totalAlerts += $this->checkFollowUpReminders($secretaryUsers);

        // 4. Overdue/Approaching Treatment Plans → admin + secretary + doctor
        $totalAlerts += $this->checkTreatmentPlans($staffUsers);

        $this->info("Dental alerts sent: {$totalAlerts}");

        return self::SUCCESS;
    }

    protected function checkOverdueLabOrders($staffUsers): int
    {
        $overdueOrders = DentalLabOrder::with(['patient', 'doctor.user'])
            ->overdue()
            ->whereNull('notification_sent_at')
            ->get();

        $count = 0;

        foreach ($overdueOrders as $order) {
            // Notify all staff (admins + secretaries)
            foreach ($staffUsers as $user) {
                $user->notify(new DentalLabOrderOverdueNotification($order));
            }

            // Also notify the doctor
            if ($order->doctor?->user && !$staffUsers->contains('id', $order->doctor->user->id)) {
                $order->doctor->user->notify(new DentalLabOrderOverdueNotification($order));
            }

            $order->update(['notification_sent_at' => now()]);
            $count++;
        }

        $this->line("  → Overdue lab orders: {$count}");
        return $count;
    }

    protected function checkDentalAppointments($secretaryUsers): int
    {
        $visits = Visit::with(['patient', 'doctor.user', 'service'])
            ->where('module', 'dental')
            ->where('status', 'scheduled')
            ->whereDate('visit_date', today())
            ->get();

        $count = 0;

        foreach ($visits as $visit) {
            // Notify doctor
            if ($visit->doctor?->user) {
                $visit->doctor->user->notify(new DentalAppointmentReminderNotification($visit));
                $count++;
            }

            // Notify secretaries
            foreach ($secretaryUsers as $secretary) {
                $secretary->notify(new DentalAppointmentReminderNotification($visit));
                $count++;
            }
        }

        $this->line("  → Dental appointment reminders: {$count}");
        return $count;
    }

    protected function checkFollowUpReminders($secretaryUsers): int
    {
        // Treatments completed 7 days ago that haven't had a follow-up reminder sent
        $treatments = DentalTreatment::with(['patient', 'doctor.user'])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', today()->subDays(7))
            ->whereNull('followup_reminder_at')
            ->get();

        $count = 0;

        foreach ($treatments as $treatment) {
            // Notify secretaries
            foreach ($secretaryUsers as $secretary) {
                $secretary->notify(new DentalFollowUpReminderNotification($treatment));
                $count++;
            }

            // Notify doctor
            if ($treatment->doctor?->user) {
                $treatment->doctor->user->notify(new DentalFollowUpReminderNotification($treatment));
                $count++;
            }

            $treatment->update(['followup_reminder_at' => now()]);
        }

        $this->line("  → Follow-up reminders: {$count}");
        return $count;
    }

    protected function checkTreatmentPlans($staffUsers): int
    {
        $count = 0;

        // Overdue plans (expected_end_date passed, not completed)
        $overduePlans = DentalTreatmentPlan::with(['patient', 'doctor.user'])
            ->whereIn('status', ['approved', 'in_progress'])
            ->whereNotNull('expected_end_date')
            ->where('expected_end_date', '<', today())
            ->whereNull('overdue_notified_at')
            ->get();

        foreach ($overduePlans as $plan) {
            foreach ($staffUsers as $user) {
                $user->notify(new DentalTreatmentPlanDueNotification($plan, 'overdue'));
            }

            if ($plan->doctor?->user && !$staffUsers->contains('id', $plan->doctor->user->id)) {
                $plan->doctor->user->notify(new DentalTreatmentPlanDueNotification($plan, 'overdue'));
            }

            $plan->update(['overdue_notified_at' => now()]);
            $count++;
        }

        // Approaching deadline (within 3 days)
        $approachingPlans = DentalTreatmentPlan::with(['patient', 'doctor.user'])
            ->whereIn('status', ['approved', 'in_progress'])
            ->whereNotNull('expected_end_date')
            ->whereBetween('expected_end_date', [today(), today()->addDays(3)])
            ->whereNull('approaching_notified_at')
            ->get();

        foreach ($approachingPlans as $plan) {
            foreach ($staffUsers as $user) {
                $user->notify(new DentalTreatmentPlanDueNotification($plan, 'approaching'));
            }

            $plan->update(['approaching_notified_at' => now()]);
            $count++;
        }

        $this->line("  → Treatment plan alerts: {$count}");
        return $count;
    }
}

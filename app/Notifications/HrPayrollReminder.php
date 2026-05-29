<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class HrPayrollReminder extends Notification
{
    use Queueable;

    public function __construct(
        protected Collection $missingEmployees,
        protected int $month,
        protected int $year,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $names = $this->missingEmployees->map(fn ($e) => $e->user?->name ?? "Employee #{$e->id}")->take(10)->implode(', ');
        $total = $this->missingEmployees->count();

        return [
            'type'         => 'hr_payroll_reminder',
            'month'        => $this->month,
            'year'         => $this->year,
            'total'        => $total,
            'message_en'   => "Payroll reminder: {$total} employee(s) still missing a salary slip for {$this->month}/{$this->year}. ({$names})",
            'message_ar'   => "تذكير الرواتب: {$total} موظف لا يزال بدون قسيمة راتب لشهر {$this->month}/{$this->year}. ({$names})",
            'action_url'   => '/admin/payroll',
        ];
    }
}

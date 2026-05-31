<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\Notifications\StaffNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Alerts admins (in-app) once when month-to-date notification spend crosses the
 * configured cap. Scheduled daily. Cap of 0 / empty disables the check.
 */
class NotificationCostCheck extends Command
{
    protected $signature = 'notifications:cost-check';

    protected $description = 'Alert admins when monthly notification cost exceeds the cap';

    public function handle(): int
    {
        $cap = (float) Setting::get('notifications_monthly_cost_cap', '0');
        if ($cap <= 0) {
            $this->info('No cost cap configured.');

            return self::SUCCESS;
        }

        $spent = (float) NotificationLog::where('created_at', '>=', now()->startOfMonth())->sum('cost');
        $alertKey = 'notif_cost_alert:'.now()->format('Y-m');

        if ($spent >= $cap && ! Cache::has($alertKey)) {
            StaffNotifier::toRoles(['admin', 'super_admin'], 'staff.cost_alert', [
                'title' => 'تنبيه: تجاوز ميزانية الإشعارات',
                'body' => "بلغت تكلفة الإشعارات هذا الشهر {$spent} وتجاوزت الحد {$cap}.",
                'url' => '/admin/notifications-hub/analytics',
                'meta' => ['title' => 'تنبيه تكلفة الإشعارات', 'body' => "التكلفة: {$spent} / الحد: {$cap}", 'url' => '/admin/notifications-hub/analytics'],
            ]);
            Cache::put($alertKey, true, now()->endOfMonth());
            $this->warn("Cost cap exceeded ({$spent} >= {$cap}) — admins alerted.");
        } else {
            $this->info("Month spend {$spent} / cap {$cap}.");
        }

        return self::SUCCESS;
    }
}

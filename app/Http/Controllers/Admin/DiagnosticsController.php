<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\ModuleManager;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

/**
 * Admin-only self-service diagnostics page. Aggregates the checks we
 * previously needed SSH + tinker to see:
 *   - /health output (system readiness)
 *   - Laravel scheduler: is the cron set up? last run?
 *   - Recent log tail (last N lines of storage/logs/laravel.log)
 *   - DB + storage writability
 *   - Telemedicine readiness blockers
 *
 * No secrets are exposed. Log tail is truncated.
 */
class DiagnosticsController extends Controller
{
    public function show()
    {
        // ── System state ───────────────────────────────────
        $moduleEnabled     = ModuleManager::isEnabled('telemedicine');
        $gateway           = app(PaymentGatewayManager::class)->getActive();
        $doctorsOnline     = Doctor::onlineEnabled()->count();
        $schedulesBookable = DoctorSchedule::whereIn('mode', ['online', 'both'])
            ->where('is_active', true)->count();

        $blockers = [];
        if (!$moduleEnabled)                $blockers[] = 'module_disabled';
        if (!$gateway)                      $blockers[] = 'no_payment_gateway';
        if ($doctorsOnline === 0)           $blockers[] = 'no_online_doctors';
        if ($schedulesBookable === 0)       $blockers[] = 'no_bookable_schedules';

        // ── DB connectivity ────────────────────────────────
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        // ── Scheduler heartbeat ────────────────────────────
        // We can detect scheduler activity by inspecting the framework/cache
        // directory — Laravel touches schedule mutex files each run.
        $scheduleCacheDir = storage_path('framework/cache/schedule-*');
        $scheduleFiles = glob($scheduleCacheDir) ?: [];
        $schedulerLastRun = null;
        foreach ($scheduleFiles as $file) {
            $schedulerLastRun = max($schedulerLastRun ?? 0, filemtime($file));
        }

        // Fallback: check the default log file mtime (scheduler writes via Log).
        $logFile = storage_path('logs/laravel.log');
        if (!$schedulerLastRun && File::exists($logFile)) {
            $schedulerLastRun = filemtime($logFile);
        }

        // ── Recent log tail (last 100 lines) ───────────────
        $logTail = '';
        if (File::exists($logFile)) {
            // safe tail: read up to last 60 KB only
            $size = filesize($logFile);
            $offset = max(0, $size - 60000);
            $handle = fopen($logFile, 'rb');
            fseek($handle, $offset);
            $logTail = fread($handle, 60000);
            fclose($handle);

            // Keep only last 100 lines
            $lines = explode("\n", $logTail);
            $logTail = implode("\n", array_slice($lines, -100));
        }

        return Inertia::render('Admin/Diagnostics', [
            'system' => [
                'app_env'          => app()->environment(),
                'app_debug'        => (bool) config('app.debug'),
                'php_version'      => PHP_VERSION,
                'laravel_version'  => app()->version(),
                'db_connected'     => $dbConnected,
                'storage_writable' => is_writable(storage_path('logs')) && is_writable(storage_path('framework')),
            ],
            'telemedicine' => [
                'module_enabled'     => $moduleEnabled,
                'doctors_online'     => $doctorsOnline,
                'schedules_bookable' => $schedulesBookable,
                'payment_gateway'    => $gateway ? class_basename($gateway) : null,
                'blockers'           => $blockers,
                'is_ready'           => empty($blockers),
            ],
            'scheduler' => [
                'last_run_at' => $schedulerLastRun ? date('Y-m-d H:i:s', $schedulerLastRun) : null,
                'minutes_ago' => $schedulerLastRun ? (int) floor((time() - $schedulerLastRun) / 60) : null,
                // Healthy cron setup runs at least every minute, so >5 min is suspicious
                'is_healthy'  => $schedulerLastRun && (time() - $schedulerLastRun) < 300,
            ],
            'log_tail' => $logTail,
        ]);
    }

    /**
     * Download the diagnostics snapshot as JSON. Useful for pasting into
     * a support ticket without copy-pasting the entire rendered page.
     * Same data as show() but without the log tail, and with a
     * machine-friendly shape.
     */
    public function export()
    {
        $moduleEnabled     = ModuleManager::isEnabled('telemedicine');
        $gateway           = app(PaymentGatewayManager::class)->getActive();
        $doctorsOnline     = Doctor::onlineEnabled()->count();
        $schedulesBookable = DoctorSchedule::whereIn('mode', ['online', 'both'])
            ->where('is_active', true)->count();

        $blockers = [];
        if (!$moduleEnabled)                $blockers[] = 'module_disabled';
        if (!$gateway)                      $blockers[] = 'no_payment_gateway';
        if ($doctorsOnline === 0)           $blockers[] = 'no_online_doctors';
        if ($schedulesBookable === 0)       $blockers[] = 'no_bookable_schedules';

        try { DB::connection()->getPdo(); $dbConnected = true; }
        catch (\Throwable) { $dbConnected = false; }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'app' => [
                'env'              => app()->environment(),
                'debug'            => (bool) config('app.debug'),
                'php_version'      => PHP_VERSION,
                'laravel_version'  => app()->version(),
            ],
            'system' => [
                'db_connected'     => $dbConnected,
                'storage_writable' => is_writable(storage_path('logs')),
            ],
            'telemedicine' => [
                'module_enabled'     => $moduleEnabled,
                'doctors_online'     => $doctorsOnline,
                'schedules_bookable' => $schedulesBookable,
                'payment_gateway'    => $gateway ? class_basename($gateway) : null,
                'blockers'           => $blockers,
                'is_ready'           => empty($blockers),
            ],
        ];

        $filename = 'doctorato-diagnostics-' . now()->format('Ymd-His') . '.json';
        return response()->json($payload, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_PRETTY_PRINT);
    }
}

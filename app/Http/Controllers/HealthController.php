<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\ModuleManager;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight JSON health endpoint for uptime monitoring + smoke tests.
 *
 * GET /health
 *   Returns HTTP 200 with a JSON body summarising critical subsystems.
 *   Returns HTTP 503 if any REQUIRED subsystem is failing.
 *
 * Intentionally avoids reading any secret values — only boolean flags
 * and counts. Safe to expose publicly.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $report = [
            'ok'        => true,
            'timestamp' => now()->toIso8601String(),
            'app'       => [
                'env'   => app()->environment(),
                'debug' => (bool) config('app.debug'),
            ],
            'checks'    => [],
        ];

        // ── DB ────────────────────────────────────────────
        try {
            DB::connection()->getPdo();
            $report['checks']['database'] = ['ok' => true];
        } catch (\Throwable $e) {
            $report['ok'] = false;
            $report['checks']['database'] = ['ok' => false, 'error' => 'unreachable'];
        }

        // ── Telemedicine ──────────────────────────────────
        if (ModuleManager::isEnabled('telemedicine')) {
            $onlineDoctors   = Doctor::onlineEnabled()->count();
            $onlineSchedules = DoctorSchedule::whereIn('mode', ['online', 'both'])
                ->where('is_active', true)
                ->count();

            $gateway = app(PaymentGatewayManager::class)->getActive();

            $report['checks']['telemedicine'] = [
                'ok'                 => $onlineDoctors > 0 && $onlineSchedules > 0 && $gateway !== null,
                'module_enabled'     => true,
                'doctors_online'     => $onlineDoctors,
                'schedules_bookable' => $onlineSchedules,
                'payment_gateway'    => $gateway ? class_basename($gateway) : null,
            ];

            if (! $report['checks']['telemedicine']['ok']) {
                $report['ok'] = false;
            }
        } else {
            $report['checks']['telemedicine'] = ['ok' => true, 'module_enabled' => false];
        }

        // ── Storage writable ──────────────────────────────
        $report['checks']['storage_writable'] = [
            'ok' => is_writable(storage_path('logs')) && is_writable(storage_path('framework')),
        ];
        if (! $report['checks']['storage_writable']['ok']) {
            $report['ok'] = false;
        }

        return response()->json($report, $report['ok'] ? 200 : 503);
    }
}

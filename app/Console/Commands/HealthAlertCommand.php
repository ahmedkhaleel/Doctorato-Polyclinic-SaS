<?php

namespace App\Console\Commands;

use App\Http\Controllers\HealthController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Runs the same checks /health exposes; when anything is red, emails
 * the admin address.
 *
 * Schedule (routes/console.php):
 *   Schedule::command('health:alert')->everyFifteenMinutes();
 *
 * Anti-spam: a single alert per unique-blocker-set is sent at most
 * once per hour. Once all blockers clear, a resolved email is sent.
 */
class HealthAlertCommand extends Command
{
    protected $signature = 'health:alert
                            {--force : Ignore the 1-hour anti-spam window}
                            {--dry-run : Log what would be sent, do not send}';

    protected $description = 'Send an email alert when /health reports a degraded subsystem';

    private const CACHE_KEY = 'health_alert:last_notified';
    private const COOLDOWN_SECONDS = 3600; // 1 hour

    public function handle(HealthController $health): int
    {
        // Invoke the controller as an HTTP callable to get the same JSON.
        $response = $health();
        $data = json_decode($response->getContent(), true);

        $isOk = $data['ok'] ?? false;

        // Build a stable "blocker fingerprint" — which subsystems are red?
        $reasons = [];
        foreach (($data['checks'] ?? []) as $check => $state) {
            if (($state['ok'] ?? true) === false) {
                $reasons[] = $check;
            }
        }
        sort($reasons);
        $fingerprint = implode(',', $reasons) ?: 'ok';

        $last = Cache::get(self::CACHE_KEY);
        $lastFingerprint = $last['fingerprint'] ?? null;
        $lastAt          = $last['at'] ?? 0;

        // ── Case 1: healthy now, was alerting before → send "resolved"
        if ($isOk) {
            if ($lastFingerprint && $lastFingerprint !== 'ok') {
                $this->sendResolved($lastFingerprint);
                Cache::put(self::CACHE_KEY, ['fingerprint' => 'ok', 'at' => time()]);
            }
            $this->info('All checks OK — nothing to alert.');
            return self::SUCCESS;
        }

        // ── Case 2: degraded — anti-spam: same fingerprint within cooldown?
        $withinCooldown = $lastFingerprint === $fingerprint
            && (time() - $lastAt) < self::COOLDOWN_SECONDS;

        if ($withinCooldown && !$this->option('force')) {
            $this->warn("Degraded: [{$fingerprint}] — already alerted " . (time() - $lastAt) . "s ago, skipping.");
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("Would alert for: [{$fingerprint}]");
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->sendAlert($data, $reasons);
        Cache::put(self::CACHE_KEY, ['fingerprint' => $fingerprint, 'at' => time()]);

        return self::SUCCESS;
    }

    private function adminEmail(): ?string
    {
        // Prefer a dedicated alert address, fall back to first super_admin.
        $setting = Setting::get('health_alert_email');
        if ($setting) return $setting;

        return User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))
            ->value('email');
    }

    private const REASON_LABELS = [
        'database'         => 'Database connection lost',
        'telemedicine'     => 'Telemedicine module degraded (see Diagnostics for the specific blocker)',
        'storage_writable' => 'Storage directory not writable',
    ];

    private function sendAlert(array $data, array $reasons): void
    {
        $email = $this->adminEmail();
        if (! $email) {
            $this->error('No admin email configured and no super_admin user — cannot send alert.');
            Log::warning('[health:alert] triggered but no recipient', ['reasons' => $reasons]);
            return;
        }

        $severity = in_array('database', $reasons, true) ? 'critical' : 'warning';
        $subject  = 'System ' . ($severity === 'critical' ? 'down' : 'degraded') . ': ' . implode(', ', $reasons);
        $prettyReasons = array_map(fn ($r) => self::REASON_LABELS[$r] ?? $r, $reasons);
        $appUrl = rtrim(config('app.url'), '/');

        $viewData = [
            'subject'     => $subject,
            'severity'    => $severity,
            'reasons'     => $prettyReasons,
            'timestamp'   => now()->toDateTimeString(),
            'environment' => app()->environment(),
            'appUrl'      => $appUrl,
            'ctaUrl'      => "{$appUrl}/ar/admin/diagnostics",
            'ctaLabel'    => 'Open Diagnostics',
            'intro'       => 'System health check has reported a degraded subsystem. Full JSON report is available at the Diagnostics page.',
        ];

        try {
            Mail::send('emails.system-alert', $viewData, function ($m) use ($email, $subject) {
                $m->to($email)->subject('[Doctorato] ' . $subject);
            });
            $this->info("Alert sent to {$email} for: " . implode(', ', $reasons));
        } catch (\Throwable $e) {
            $this->error('Failed to send alert: ' . $e->getMessage());
            Log::error('[health:alert] mail failed', [
                'error' => $e->getMessage(),
                'reasons' => $reasons,
            ]);
        }
    }

    private function sendResolved(string $previousFingerprint): void
    {
        $email = $this->adminEmail();
        if (! $email) return;

        $viewData = [
            'subject'     => 'All systems restored',
            'severity'    => 'resolved',
            'reasons'     => [$previousFingerprint],
            'timestamp'   => now()->toDateTimeString(),
            'environment' => app()->environment(),
            'intro'       => 'Previously-reported issues have cleared. All health checks are green again.',
        ];

        try {
            Mail::send('emails.system-alert', $viewData, function ($m) use ($email) {
                $m->to($email)->subject('[Doctorato] RESOLVED — System back to healthy');
            });
            $this->info("Resolved notification sent to {$email}");
        } catch (\Throwable $e) {
            Log::error('[health:alert] resolved mail failed', ['error' => $e->getMessage()]);
        }
    }
}

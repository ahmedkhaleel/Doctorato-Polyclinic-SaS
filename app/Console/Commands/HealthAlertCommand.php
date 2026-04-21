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

    private function sendAlert(array $data, array $reasons): void
    {
        $email = $this->adminEmail();
        if (! $email) {
            $this->error('No admin email configured and no super_admin user — cannot send alert.');
            Log::warning('[health:alert] triggered but no recipient', ['reasons' => $reasons]);
            return;
        }

        $subject = '[Doctorato] System alert: ' . implode(', ', $reasons);
        $body = "System health check has reported a degraded subsystem.\n\n"
              . "Affected: " . implode(', ', $reasons) . "\n"
              . "Time: " . now()->toDateTimeString() . "\n"
              . "Environment: " . app()->environment() . "\n\n"
              . "Full report:\n"
              . json_encode($data, JSON_PRETTY_PRINT)
              . "\n\nDiagnostics page:\n"
              . rtrim(config('app.url'), '/') . "/ar/admin/diagnostics\n";

        try {
            Mail::raw($body, function ($m) use ($email, $subject) {
                $m->to($email)->subject($subject);
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

        $subject = '[Doctorato] RESOLVED — System back to healthy';
        $body = "Previously-reported issues are now cleared.\n\n"
              . "Previously failing: {$previousFingerprint}\n"
              . "Recovered at: " . now()->toDateTimeString() . "\n";

        try {
            Mail::raw($body, function ($m) use ($email, $subject) {
                $m->to($email)->subject($subject);
            });
            $this->info("Resolved notification sent to {$email}");
        } catch (\Throwable $e) {
            Log::error('[health:alert] resolved mail failed', ['error' => $e->getMessage()]);
        }
    }
}

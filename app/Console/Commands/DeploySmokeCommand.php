<?php

namespace App\Console\Commands;

use App\Http\Controllers\HealthController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Post-deploy smoke test. Runs a short list of invariants that must
 * be true after every deploy; exits non-zero on the first failure so
 * a CI/cron wrapper can immediately invoke deploy:rollback.
 *
 * Checks:
 *   1. DB reachable
 *   2. /health returns 200 OR a 503 whose checks match expected state
 *   3. Critical routes resolve (patient doctors list, booking, admin)
 *   4. Vite manifest contains required Inertia pages (catches the
 *      shipped-without-rebuild failure mode we hit before)
 *   5. Storage writable
 *
 * Used by .github/workflows/deploy.yml after the SSH deploy step.
 */
class DeploySmokeCommand extends Command
{
    protected $signature = 'deploy:smoke
                            {--json : Emit JSON result}';

    protected $description = 'Fast post-deploy invariants check';

    private array $failures = [];

    public function handle(): int
    {
        $this->assertDatabase();
        $this->assertCriticalRoutes();
        $this->assertManifest();
        $this->assertStorage();
        $this->assertHealthShape();

        $ok = empty($this->failures);

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok'       => $ok,
                'failures' => $this->failures,
            ], JSON_PRETTY_PRINT));
        } elseif ($ok) {
            $this->info('✓ All smoke checks passed.');
        } else {
            $this->error('✗ Smoke tests failed:');
            foreach ($this->failures as $f) {
                $this->line("  - {$f}");
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function flagFail(string $msg): void
    {
        $this->failures[] = $msg;
    }

    private function assertDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $count = DB::table('users')->count();
            if ($count === 0) {
                $this->flagFail('users table is empty — did seeds get skipped?');
            }
        } catch (\Throwable $e) {
            $this->flagFail('DB connection: ' . $e->getMessage());
        }
    }

    private function assertCriticalRoutes(): void
    {
        $required = [
            'health',
            'admin.login',
            'patient.login',
            'patient.online-consultations.doctors',
            'patient.online-consultations.book',
            'admin.diagnostics',
        ];

        $known = collect(Route::getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->values()
            ->all();

        foreach ($required as $name) {
            if (! in_array($name, $known, true)) {
                $this->flagFail("Missing route: {$name}");
            }
        }
    }

    private function assertManifest(): void
    {
        $manifestPath = public_path('build/manifest.json');
        if (! File::exists($manifestPath)) {
            $this->flagFail('public/build/manifest.json is missing — forgot npm run build?');
            return;
        }

        $manifest = json_decode(File::get($manifestPath), true) ?: [];
        $required = [
            'resources/js/Pages/Patient/OnlineConsultation/Book.vue',
            'resources/js/Pages/Patient/OnlineConsultation/Doctors.vue',
            'resources/js/Pages/Patient/Dashboard.vue',
            'resources/js/Pages/Admin/Dashboard.vue',
            'resources/js/Pages/Admin/Diagnostics.vue',
        ];
        foreach ($required as $page) {
            if (! isset($manifest[$page])) {
                $this->flagFail("Manifest missing page: {$page}");
            }
        }
    }

    private function assertStorage(): void
    {
        foreach ([storage_path('logs'), storage_path('framework')] as $dir) {
            if (! is_writable($dir)) {
                $this->flagFail("Not writable: {$dir}");
            }
        }
    }

    private function assertHealthShape(): void
    {
        try {
            $resp = app(HealthController::class)();
            $code = $resp->getStatusCode();
            if (! in_array($code, [200, 503], true)) {
                $this->flagFail("/health returned unexpected status {$code}");
            }
            $body = json_decode($resp->getContent(), true);
            foreach (['ok', 'checks'] as $k) {
                if (! array_key_exists($k, $body ?? [])) {
                    $this->flagFail("/health body missing key '{$k}'");
                }
            }
        } catch (\Throwable $e) {
            $this->flagFail('/health threw: ' . $e->getMessage());
        }
    }
}
